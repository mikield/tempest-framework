<?php

namespace Tempcord\Registries;

use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Request\Option;
use Exception;
use Tempcord\Attributes\SlashCommands\Command;
use Tempest\Console\Console;
use Tempest\Container\Singleton;
use Throwable;
use function Tempest\get;

#[Singleton]
final class CommandRegistry
{
    private array $commands;

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
                function (Interaction $interaction, Collection $params) use ($command, $console) {

                    $commandInstance = get($command->className);

                    //@todo Move to DiscordCommand::mapOptionValues
                    $params->map(function (Option $option) use ($command, $commandInstance) {
                        if (array_key_exists($option->name, $command->options)) {
                            $command->options[$option->name]->set($commandInstance, $option->value);
                        }
                    });

                    try {
                        if (!$command->hasRunMethod) {
                            throw new \LogicException('Command should implement [run] method.');
                        }
                        return $commandInstance->run($interaction);
                    } catch (Throwable $e) {
                        $console->error($e->getMessage());
                    }

                    return null;
                }
            );
        }
    }

}