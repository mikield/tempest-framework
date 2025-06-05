<?php

namespace Tempcord\Attributes\SlashCommands;

use Attribute;
use Discord\Builders\CommandBuilder;
use Discord\Discord;
use Discord\Parts\Permissions\RolePermission;
use React\Promise\PromiseInterface;
use RuntimeException;
use Tempest\Reflection\ClassReflector;
use function React\Async\await;
use function Tempest\get;
use function Tempest\Support\Arr\flat_map;
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
            ->setDescription($this->description)
            ->setGuildId($this->guildId)
            ->setNsfw($this->isNsfw)
            ->setDefaultMemberPermissions($this->defaultRolePermissions ? ($this->defaultRolePermissions)($discord) : null)
            ->setDmPermission($this->directMessage)
            ->setType($this->type);

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

    public function mapOptions(\Discord\Helpers\Collection $params, Discord $discord): void
    {
        $params->map(function (\Discord\Parts\Interactions\Request\Option $option) use ($discord) {
            if (array_key_exists($option->name, $this->options)) {

                $value = match ($option->type) {
                    \Discord\Parts\Interactions\Command\Option::USER => await($discord->users->fetch($option->value)),
                    \Discord\Parts\Interactions\Command\Option::CHANNEL => $discord->getChannel($option->value),
                    \Discord\Parts\Interactions\Command\Option::ROLE => throw new RuntimeException('Not implemented'),
                    default => $option->value,
                };

                $this->options[$option->name]->set($this->instance, $value);
            }
        });
    }
}