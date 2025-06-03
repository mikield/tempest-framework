<?php

namespace Tempcord\Attributes\SlashCommands;

use Attribute;
use ReflectionNamedType;
use ReflectionProperty;
use function Tempest\Support\str;

#[Attribute]
final readonly class Option
{
    private ReflectionProperty $reflector;

    public function __construct(
        private string  $description,
        private ?string $name = null,
        private bool    $required = false,
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
            //@todo Add more options: UserResolvable,ChannelResolvable,RoleResolvable,Mentionable
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

    public function set(object $class, float|bool|int|string|null $value): void
    {
        $this->reflector->setRawValue($class, $value);
    }
}