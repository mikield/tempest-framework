<?php

namespace Tempcord\Registries;

use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Enums\InteractionCallbackType;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Parts\ApplicationCommandInteractionDataOptionStructure;
use Ragnarok\Fenrir\Parts\ApplicationCommandOptionChoice;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;
use Tempcord\Attributes\Subcommand;
use Tempcord\Attributes\SubcommandGroup;
use Tempcord\Contracts\Autocomplete;
use Tempcord\Extensions\AllCommandExtension;
use Tempcord\Extensions\InteractionCallbackBuilder;
use Tempest\Console\Console;
use Tempest\Container\Singleton;
use Throwable;
use function React\Async\await;
use function Tempest\Support\Arr\map_iterable;

#[Singleton]
final class CommandsRegistry
{
    /** @var array<Command> */
    private array $commands = [];

    public function __construct(
        public readonly AllCommandExtension $extension
    )
    {
    }

    public function add(Command $command): void
    {
        if ($command->guildId) {
            $this->commands[$command->guildId] = $command;
            return;
        }

        if (array_key_exists($command->name, $this->commands)) {
            $command->mergeOptions($this->commands[$command->name]);
        }

        $this->commands[$command->name] = $command;
    }

    /**
     * @throws Throwable
     */
    public function register(Console $console, Discord $discord): void
    {
        if (empty($this->commands)) {
            $console->warning('No commands to register.');
            return;
        }

        $register = static fn(string $through, int $applicationId) => static function (Command $command) use ($console, $discord, $applicationId, $through) {
            try {
                $command = await($discord->rest->{$through}->createApplicationCommand(
                    $applicationId,
                    $command->build
                ));
                $console->success('Command "' . $command->name . '" registered.');
            } catch (Throwable $throwable) {
                $console->error($throwable->getMessage());
            }
        };

        try {
            $application = await($discord->rest->application->getCurrent());
            map_iterable($this->commands, $register('globalCommand', $application->id));
        } catch (Throwable $throwable) {
            $console->error($throwable->getMessage());
        }
    }

    public function listen(Console $console): void
    {
        $console->header('Starting Commands');
        foreach ($this->commands as $command) {
            foreach ($command->handlers as $key => $handler) {
                $this->extension->bind(
                    command: $key,
                    listener: function (CommandInteraction $interaction) use ($console, $handler) {
                        try {
                            $handler($interaction);
                        } catch (\Throwable $e) {
                            $console->error($e->getMessage());
                        }
                    },
                    autocomplete: function (CommandInteraction $interaction) use ($command) {
                        [$option, $interactionOption] = $this->resolveFocusedAndParam($interaction->interaction->data->options, $command);

                        if (!$option->autocomplete instanceof Autocomplete) {
                            return null;
                        }

                        $value = $option->autocomplete->handle($interaction, $interactionOption->value);

                        $choices = is_array($value) ? $value : [$value];

                        $choices = array_slice($choices, 0, 25);

                        $choices = array_map(function ($choice, $key) use ($choices) {
                            if ($choice instanceof ApplicationCommandOptionChoice) {
                                return $choice;
                            }

                            if (array_is_list($choices)) {
                                $key = $choice;
                            }

                            if (is_int($key)) {
                                $key = (string)$key;
                            }

                            $applicationCommandOptionChoice = new ApplicationCommandOptionChoice();
                            $applicationCommandOptionChoice->name = $key;
                            $applicationCommandOptionChoice->value = $choice;

                            return $applicationCommandOptionChoice;
                        }, $value, array_keys($value));

                        $interaction->createInteractionResponse(
                            InteractionCallbackBuilder::new()
                                ->setType(InteractionCallbackType::APPLICATION_COMMAND_AUTOCOMPLETE_RESULT)
                                ->setChoices($choices)
                        );
                    }
                );
                $console->success('Command "' . $key . '" listened.');
            }
        }
    }

    /**
     * @param array $interactionOptions — array of ApplicationCommandInteractionDataOptionStructure
     * @param Command|SubcommandGroup|Subcommand $definition — Tempcord definition
     * @return array{ Option, ApplicationCommandInteractionDataOptionStructure }|null
     */
    private function resolveFocusedAndParam(array $interactionOptions, Command|SubcommandGroup|Subcommand $definition): ?array
    {
        /** @var ApplicationCommandInteractionDataOptionStructure $option */
        foreach ($interactionOptions as $option) {
            $type = $option->type->value;
            $name = $option->name;

            // If SUB_COMMAND_GROUP or SUB_COMMAND, go deeper
            if (in_array($type, [1, 2], true)) {
                // $definition->options[$name] should be the nested command/group
                $nextDefinition = $definition->options[$name] ?? null;

                if ($nextDefinition && !empty($option->options)) {
                    $result = $this->resolveFocusedAndParam($option->options, $nextDefinition);
                    if ($result !== null) {
                        return $result;
                    }
                }
            } else if ((isset($option->focused) && $option->focused === true) && $definition->options[$name]) {
                return [
                    $definition->options[$name],
                    $option
                ];
            }
        }

        return null;
    }

}