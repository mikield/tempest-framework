<?php

namespace Tempcord\Registries;

use Generator;
use Ragnarok\Fenrir\Command\AllCommandExtension;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Exceptions\Rest\Helpers\Command\InvalidCommandNameException;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Parts\ApplicationCommandInteractionDataOptionStructure;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandOptionBuilder;
use Tempcord\Attributes\Command;
use Tempest\Console\Console;
use Tempest\Container\Singleton;
use Throwable;
use function Freezemage\ArrayUtils\find;
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

    private function drillName(array $options): Generator
    {
        /** @var CommandOptionBuilder|null $subCommand */
        $subCommand = find($options, function (CommandOptionBuilder $option) {
            return in_array($option->getType(), [
                ApplicationCommandOptionType::SUB_COMMAND,
                ApplicationCommandOptionType::SUB_COMMAND_GROUP,
            ], true);
        });

        if (!is_null($subCommand)) {
            yield $subCommand->getName();

            yield from $this->drillName($subCommand->getOptions() ?? []);
        }
    }


    public function listen(Console $console): void
    {

        foreach ($this->commands as $command) {
            foreach ($command->handlers as $key => $handler) {
                $this->extension->on($key, function (CommandInteraction $interaction) use ($console, $handler) {
                    try {
                        $handler($interaction);
                    } catch (\Throwable $e) {
                        $console->error($e->getMessage());
                    }
                });
                $console->success('Command "' . $key . '" listened.');
            }
        }


//        foreach ($this->commands as $command) {
//            $discord->listenCommand(
//                $command->name,
//                function (Interaction $interaction, Collection $params) use ($command, $console, $discord) {
//                    $command->mapOptions($interaction, $params, $discord);
//
//                    try {
//                        return $command->handle($interaction);
//                    } catch (Throwable $e) {
//
//                    }
//
//                    return null;
//                },
//                function (Interaction $interaction) use ($command, $discord) {
//                    foreach ($interaction->data->options as $option) {
//                        $autocomplete = $command->options[$option->name]->getAutocomplete();
//
//                        if (null === $autocomplete) {
//                            continue;
//                        }
//
//                        $value = $autocomplete->handle($interaction, $option->value);
//
//                        $choices = is_array($value) ? $value : [$value];
//
//                        $choices = array_slice($choices, 0, 25);
//
//                        if ($option->focused && $value) {
//                            return array_map(function ($choice, $key) use ($discord, $choices) {
//                                if ($choice instanceof Choice) {
//                                    return $choice;
//                                }
//
//                                if (array_is_list($choices)) {
//                                    $key = $choice;
//                                }
//
//                                if (is_int($key)) {
//                                    $key = (string)$key;
//                                }
//
//
//                                return Choice::new($discord, $choice, $key);
//                            }, $value, array_keys($value));
//                        }
//                    }
//                }
//            );
//        }
    }

}