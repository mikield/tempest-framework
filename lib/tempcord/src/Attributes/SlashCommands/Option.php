<?php

namespace Tempcord\Attributes\SlashCommands;

use Attribute;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Role;
use Discord\Parts\User\User;
use ReflectionNamedType;
use ReflectionProperty;
use Tempcord\Contracts\Autocomplete;
use function Tempest\Support\str;

#[Attribute]
final readonly class Option
{
    private ReflectionProperty $reflector;

    public function __construct(
        //@todo Add more options for building the Option (from DiscordPHP)
        private string        $description,
        private ?string       $name = null,
        private bool          $required = false,
        private ?Autocomplete $autocomplete = null,
    )
    {
    }

    public function getName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        return str($this->reflector->name)->toString();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getType(): int
    {
        if (!$this->reflector->hasType()) {
            throw new \LogicException('Command option does not have type');
        }

        $type = $this->reflector->getType();

        assert($type instanceof ReflectionNamedType);

        //array, bool, callable, float, int, null, object, string, false, iterable, mixed, never, true, void,
        return match ($type->getName()) {
            'string' => \Discord\Parts\Interactions\Command\Option::STRING,
            'int' => \Discord\Parts\Interactions\Command\Option::INTEGER,
            'float' => \Discord\Parts\Interactions\Command\Option::NUMBER,
            'bool' => \Discord\Parts\Interactions\Command\Option::BOOLEAN,
            User::class => \Discord\Parts\Interactions\Command\Option::USER,
            Channel::class => \Discord\Parts\Interactions\Command\Option::CHANNEL,
            Role::class => \Discord\Parts\Interactions\Command\Option::ROLE,
            //@todo Add more options: Mentionable
            default => throw new \LogicException('Command option type not supported'),
        };
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setReflector(ReflectionProperty $reflector): void
    {
        $this->reflector = $reflector;
    }

    public function set(object $class, float|bool|int|string|null|User|Channel $value): void
    {
        $this->reflector->setRawValue($class, $value);
    }

    public function getAutocomplete(): ?Autocomplete
    {
        return $this->autocomplete;
    }
}