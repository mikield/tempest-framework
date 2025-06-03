<?php

declare(strict_types=1);

use Discord\WebSockets\Intents;
use Tempcord\TempcordConfig;

use function Tempest\env;

return new TempcordConfig(
    token: env('DISCORD_TOKEN'),
    intents: Intents::getDefaultIntents(),
);
