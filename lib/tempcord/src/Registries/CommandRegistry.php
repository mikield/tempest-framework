<?php

namespace Tempcord\Registries;

use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Parts\Interactions\Command\Choice;
use Discord\Parts\Interactions\Interaction;
use Exception;
use Tempcord\Attributes\SlashCommands\Command;
use Tempest\Console\Console;
use Tempest\Container\Singleton;
use Throwable;

#[Singleton]
final class CommandRegistry
{
    /** @var array<Command> */
    private array $commands = [];

    public function __construct()
    {

    }

    public function add(Command $command): void
    {
        $this->commands[$command->name] = $command;
    }

    /**
     * @throws Exception
     */
    public function register(Console $console, Discord $discord): void
    {
        foreach ($this->commands as $name => $command) {
            $discord->application->commands->save(
                $discord->application->commands->create(
                    $command->getCommandBuilder($discord)->toArray()
                ),
            );

            $console->info('Registered command: ' . $name);
        }
    }

    public function listen(Console $console, Discord $discord): void
    {
        foreach ($this->commands as $name => $command) {
            $discord->listenCommand(
                $name,
                function (Interaction $interaction, Collection $params) use ($command, $console, $discord) {
                    $command->mapOptions($params, $discord);

                    try {
                        return $command->handle($interaction);
                    } catch (Throwable $e) {
                        $console->error($e->getMessage());
                    }

                    return null;
                },
                function (Interaction $interaction) use ($command, $discord) {
                    foreach ($interaction->data->options as $option) {
                        $autocomplete = $command->options[$option->name]->getAutocomplete();

                        if (null === $autocomplete) {
                            continue;
                        }

                        $value = $autocomplete->handle($interaction, $option->value);

                        $choices = is_array($value) ? $value : [$value];

                        if ($option->focused && $value) {
                            return array_map(function ($choice, $key) use ($discord, $choices) {
                                if ($choice instanceof Choice) {
                                    return $choice;
                                }

                                if (array_is_list($choices)) {
                                    $key = $choice;
                                }

                                if (is_int($key)) {
                                    $key = (string)$key;
                                }


                                return Choice::new($discord, $choice, $key);
                            }, $value, array_keys($value));
                        }
                    }
                }
            );
        }
    }

}