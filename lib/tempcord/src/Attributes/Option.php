<?php

namespace Tempcord\Attributes;

use Attribute;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Parts\Channel;
use Ragnarok\Fenrir\Parts\Role;
use Ragnarok\Fenrir\Parts\User;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use Tempcord\Contracts\Autocomplete;
use Tempcord\Traits\HasAttributes;
use function Tempest\Support\str;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class Option
{
    use HasAttributes;

    public ReflectionProperty|ReflectionParameter $reflector {
        set {
            $this->reflector = $value;
        }
    }

    public string $name {
        get {
            if ($this->hasAttribute('name')) {
                return $this->getAttribute('name');
            }

            return str($this->reflector->name)->toString();
        }
    }

    public ApplicationCommandOptionType $type {
        get {
            if (!$this->reflector->hasType()) {
                throw new \LogicException('Command option does not have type');
            }

            $type = $this->reflector->getType();

            assert($type instanceof ReflectionNamedType);

            //array, bool, callable, float, int, null, object, false, iterable, mixed, never, true, void,
            return match ($type->getName()) {
                'string' => ApplicationCommandOptionType::STRING,
                'int' => ApplicationCommandOptionType::INTEGER,
                'float' => ApplicationCommandOptionType::NUMBER,
                'bool' => ApplicationCommandOptionType::BOOLEAN,
                User::class => ApplicationCommandOptionType::USER,
                Channel::class => ApplicationCommandOptionType::CHANNEL,
                Role::class => ApplicationCommandOptionType::ROLE,
                //@todo Add more options: Mentionable
                default => throw new \LogicException('Command option type not supported'),
                //@todo maybe add some DTO mapper!? To map modal data to an object
            };
        }
    }

    public function __construct(
        //@todo Add more options for building the Option (from DiscordPHP)
        public string        $description,
        ?string              $name = null,
        public bool          $required = false,
        public ?Autocomplete $autocomplete = null,
    )
    {
        $this->setAttribute('name', $name);
    }

    public function set(object $class, float|bool|int|string|null|User|Channel $value): void
    {
        $this->reflector->setRawValue($class, $value);
    }
}