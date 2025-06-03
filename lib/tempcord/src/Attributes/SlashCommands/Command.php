<?php

namespace Tempcord\Attributes\SlashCommands;

use Attribute;
use Discord\Builders\CommandBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Command\Option;
use Tempest\Reflection\ClassReflector;
use function Tempest\Support\str;

#[Attribute]
final class Command
{
    public string $className {
        get {
            return $this->reflector->getName();
        }
    }

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

    public bool $hasRunMethod {
        get {
            return $this->reflector->getReflection()->hasMethod('run');
        }
    }

    /** @var array<Option> */
    public array $options {
        get {
            $options = [];
            foreach ($this->reflector->getProperties() as $property) {
                if ($property->hasAttribute(Option::class)) {
                    $option = $property->getAttribute(Option::class);
                    $option->setReflector($property->getReflection());
                    $options[$property->getName()] = $option;
                }
            }
            return $options;
        }
    }

    private readonly ClassReflector $reflector;

    public function __construct(
        public ?string $command = null,
        public ?string $description = null,
        public int     $type = \Discord\Parts\Interactions\Command\Command::CHAT_INPUT
    )
    {
    }

    public function getCommandBuilder(Discord $discord): CommandBuilder
    {
        $command = CommandBuilder::new()
            ->setName($this->name)
            ->setDescription($this->description)
            ->setType($this->type);

        if (!empty($this->options)) {
            foreach ($this->options as $option) {
                $command = $command->addOption(
                    new Option(
                        discord: $discord,
                        attributes: [
                            'name' => $option->getName(),
                            'description' => $option->getDescription(),
                            'type' => $option->getType(),
                            'required' => $option->isRequired(),
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
}