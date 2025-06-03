<?php

namespace Tempcord;

use Discord\Discord;
use Discord\Exceptions\IntentException;
use Tempcord\Registries\CommandRegistry;
use Tempest\Console\Console;
use Tempest\Container\Container;
use Tempest\Container\Initializer;
use Tempest\Container\Singleton;
use Tempest\Log\Logger;

final readonly class TempcordInitializer implements Initializer
{
    /**
     * @throws IntentException
     */
    #[Singleton]
    public function initialize(Container $container): Tempcord
    {
        $config = $container->get(TempcordConfig::class);

        return new Tempcord(
            discord: new Discord([
                'token' => $config->token,
                'intents' => $config->intents,
                'logger' => $container->get(Logger::class),
            ]),
            commandRegistry: $container->get(CommandRegistry::class),
            console: $container->get(Console::class)
        );
    }
}