<?php

namespace Tempcord\Attributes\SlashCommands;

use Attribute;
use Discord\Builders\CommandBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Permissions\RolePermission;
use React\Promise\PromiseInterface;
use RuntimeException;
use Tempest\Reflection\ClassReflector;
use function React\Async\await;
use function Tempest\get;
use function Tempest\Support\Arr\flat_map;
use function Tempest\Support\Arr\last;
use function Tempest\Support\Arr\map_with_keys;
use function Tempest\Support\str;

#[Attribute]
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
                return $this->command;
            }

            $commandName = str($this->reflector->getShortName())
                ->replaceEnd('Command', '')
                ->replaceStart('Command', '')
                ->snake(':')
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

                    $option->setReflector($property->getReflection());
                    $options[$property->getName()] = $option;
                }
            }
            return $options;
        }
    }

    private ?\Closure $defaultRolePermissions {
        get {
            if (!$this->permissions) {
                return null;
            }
            return fn(Discord $discord) => new RolePermission($discord, map_with_keys(
                $this->permissions,
                fn($permission) => [$permission => true]
            ))->__toString();
        }
    }

    private readonly ClassReflector $reflector;

    public function __construct(
        public ?string $command = null,
        public ?string $description = null,
        public ?int    $guildId = null,
        public bool    $isNsfw = false,
        public array   $permissions = [],
        public bool    $directMessage = true,
        public int     $type = \Discord\Parts\Interactions\Command\Command::CHAT_INPUT
    )
    {
    }

    public function getCommandBuilder(Discord $discord): CommandBuilder
    {
        $command = CommandBuilder::new()
            ->setName($this->name)
//            ->setNsfw($this->isNsfw) //@todo Unhandled by DiscordPHP
//            ->setDmPermission($this->directMessage) //@todo Unhandled by DiscordPHP
            ->setType($this->type);


        if ($this->type === \Discord\Parts\Interactions\Command\Command::CHAT_INPUT) {

            if (!$this->description) {
                throw new \LogicException("Description for command [$this->name] is required when type=CHAT_INPUT");
            }

            $command->setDescription($this->description);
        }

        if ($this->guildId) {
            $command->setGuildId($this->guildId);
        }

        if ($this->defaultRolePermissions) {
            $command->setDefaultMemberPermissions(($this->defaultRolePermissions)($discord));
        }


        if (!empty($this->options)) {
            foreach ($this->options as $option) {
                $command = $command->addOption(
                    new \Discord\Parts\Interactions\Command\Option(
                        discord: $discord,
                        attributes: [
                            'name' => $option->getName(),
                            'description' => $option->getDescription(),
                            'type' => $option->getType(),
                            'required' => $option->isRequired(),
                            'autocomplete' => !is_null($option->getAutocomplete()),
                        ]
                    )
                );
            }
        }

        return $command;
    }

    public function setReflector(ClassReflector $reflector): Command
    {
        $this->reflector = $reflector;
        return $this;
    }

    public function handle(\Discord\Parts\Interactions\Interaction $interaction): PromiseInterface
    {
        if (!$this->reflector->getReflection()->hasMethod('__invoke')) {
            throw new RuntimeException('Command [' . $this->name . '] does not have a method __invoke() method.');
        }

        return call_user_func([$this->instance, '__invoke'], $interaction);
    }

    public function mapOptions(Interaction $interaction, \Discord\Helpers\Collection $params, Discord $discord): void
    {
        $params->map(function (\Discord\Parts\Interactions\Request\Option $option) use ($discord, $interaction) {
            if (array_key_exists($option->name, $this->options)) {

                $value = match ($option->type) {
                    \Discord\Parts\Interactions\Command\Option::USER => await($discord->users->fetch($option->value)),
                    \Discord\Parts\Interactions\Command\Option::CHANNEL => $discord->getChannel($option->value),
                    \Discord\Parts\Interactions\Command\Option::ROLE => await($interaction->guild->roles->fetch($option->value)),
                    //@todo need a proxy object that will proxy all props to Channel or User
                    \Discord\Parts\Interactions\Command\Option::MENTIONABLE => throw new RuntimeException('Not implemented'),
                    default => $option->value,
                };

                $this->options[$option->name]->set($this->instance, $value);
            }
        });
    }
}