<?php

namespace Tempcord\Attributes;

use Attribute;
use BackedEnum;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Enums\ApplicationCommandTypes;
use Ragnarok\Fenrir\Exceptions\Rest\Helpers\Command\InvalidCommandNameException;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandOptionBuilder;
use React\Promise\PromiseInterface;
use RuntimeException;
use Tempest\Reflection\ClassReflector;
use Tempest\Reflection\MethodReflector;
use function React\Async\await;
use function Tempest\get;
use function Tempest\Support\str;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Command
{
    /**
     * A Singleton instance of the command
     * @var object|null
     */
    private static ?object $_instance = null;

    /**
     * Computed getter for Singleton instance
     * @var object
     */
    public object $instance {
        get {
            if (self::$_instance === null) {
                self::$_instance = get($this->reflector->getName());
            }

            return $this::$_instance;
        }
    }

    /**
     * Computed getter for command name
     *
     * If none command is provided - it will take name from the classname
     *
     * @var string
     */
    public string $name {
        get {
            if ($this->command) {
                return $this->command instanceof BackedEnum ? $this->command->value : $this->command;
            }

            $commandName = str($this->reflector->getShortName())
                ->replaceEnd('Command', '')
                ->replaceStart('Command', '')
                ->snake('_')
                ->lower();

            return $commandName->toString();
        }
    }

    /**
     * Options mapper
     *
     * Maps all command options using Reflection API
     *
     * @var Option[]
     */
    public array $options {
        get {
            $options = [];
            foreach ($this->reflector->getProperties() as $property) {
                if ($property->hasAttribute(Option::class)) {
                    $option = $property->getAttribute(Option::class);

                    if (!$option) {
                        continue;
                    }

                    $option->reflector = $property->getReflection();
                    $options[$option->name] = $option;
                }
            }
            return $options;
        }
    }

    private ?\Closure $defaultRolePermissions {
        get {
            return null;
//            if (!$this->permissions) {
//                return null;
//            }
//            return fn(Discord $discord) => new RolePermission($discord, map_with_keys(
//                $this->permissions,
//                fn($permission) => [$permission => true]
//            ))->__toString();
        }
    }

    public ClassReflector $reflector {
        set {
            $this->reflector = $value;
        }
    }
    private string|null|BackedEnum $command;

    public function __construct(
        string|BackedEnum|null         $name = null,
        public ?string                 $description = null,
        public ?int                    $guildId = null,
        public bool                    $isNsfw = false,
        public array                   $permissions = [],
        public bool                    $directMessage = true,
        public ApplicationCommandTypes $type = ApplicationCommandTypes::CHAT_INPUT
    )
    {
        $this->command = $name;
    }

    /**
     * @throws InvalidCommandNameException
     */
    public function build(): CommandBuilder
    {
        $isInGroup = $this->reflector->hasAttribute(SubcommandGroup::class);

        $command = CommandBuilder::new()
            ->setName($this->name)
            ->setNsfw($this->isNsfw)
            ->setDmPermission($this->directMessage)
            ->setType($this->type);

        if ($this->type === ApplicationCommandTypes::CHAT_INPUT) {

            if (!$this->description) {
                throw new \LogicException("Description for command [$this->name] is required when type=CHAT_INPUT");
            }

            $command->setDescription($this->description);
        }

        if ($this->defaultRolePermissions) {
//            $command->setDefaultMemberPermissions(($this->defaultRolePermissions)($discord));
        }

        if ($isInGroup) {
            /** @var SubcommandGroup $group */
            $group = $this->reflector->getAttribute(SubcommandGroup::class);
            $subcommandGroup = CommandOptionBuilder::new()
                ->setName($group->name)
                ->setDescription($group->description)
                ->setType(ApplicationCommandOptionType::SUB_COMMAND_GROUP);

            $command->addOption($subcommandGroup);
        }

        $subcommands = [];
        $methods = $this->reflector->getPublicMethods();
        foreach ($methods as $method) {
            if ($method->hasAttribute(Subcommand::class)) {
                $subcommands[] = $this->buildSubcommand($method);
            }
        }

        if (!empty($subcommands)) {
            foreach ($subcommands as $subcommand) {
                if (isset($subcommandGroup)) {
                    $subcommandGroup->addOption($subcommand);
                    continue;
                }

                $command->addOption($subcommand);
            }

            return $command;
        }

        if (!$this->reflector->getReflection()->hasMethod('__invoke')) {
            throw new RuntimeException('Class [' . $this->reflector->getName() . '] should declare public sub-commands or have an __invoke method');
        }

        $options = $this->buildOptions($this->reflector->getMethod('__invoke'));

        foreach ($options as $option) {
            $command->addOption($option);
        }


        return $command;
    }

    private function buildSubcommand(MethodReflector $method): CommandOptionBuilder
    {
        $options = $this->buildOptions($method);
        /** @var Subcommand $subcommand */
        $subcommand = $method->getAttribute(Subcommand::class);

        $command = new CommandOptionBuilder()
            ->setName($subcommand->name)
            ->setDescription($subcommand->description)
            ->setType(ApplicationCommandOptionType::SUB_COMMAND);

        foreach ($options as $option) {
            $command->addOption($option);
        }

        return $command;
    }

    private function buildOptions(MethodReflector $method): array
    {
        $options = [];
        foreach ($method->getParameters() as $parameter) {
            if (!$parameter->hasAttribute(Option::class)) {
                continue;
            }
            /** @var Option $option */
            $option = $parameter->getAttribute(Option::class);
            $option->reflector = $parameter->getReflection();
            $options[] = CommandOptionBuilder::new()
                ->setName($option->name)
                ->setDescription($option->description)
                ->setType($option->type)
                ->setRequired($option->required)
                ->setAutoComplete($option->autocomplete !== null);
        }

        return $options;
    }

    public function handle(CommandInteraction $interaction): PromiseInterface
    {
        //@todo Refactor to new style
        return call_user_func([$this->instance, '__invoke'], $interaction);
    }

//    public function mapOptions(Interaction $interaction, \Discord\Helpers\Collection $params, Discord $discord): void
//    {
//        $params->map(function (\Discord\Parts\Interactions\Request\Option $option) use ($discord, $interaction) {
//            if (array_key_exists($option->name, $this->options)) {
//
//                $value = match ($option->type) {
//                    \Discord\Parts\Interactions\Command\Option::USER => await($discord->users->fetch($option->value)),
//                    \Discord\Parts\Interactions\Command\Option::CHANNEL => $discord->getChannel($option->value),
//                    \Discord\Parts\Interactions\Command\Option::ROLE => await($interaction->guild->roles->fetch($option->value)),
//                    //@todo need a proxy object that will proxy all props to Channel or User
//                    \Discord\Parts\Interactions\Command\Option::MENTIONABLE => throw new RuntimeException('Not implemented'),
//                    default => $option->value,
//                };
//
//                $this->options[$option->name]->set($this->instance, $value);
//            }
//        });
//    }


}