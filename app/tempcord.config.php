<?php

declare(strict_types=1);

use Ragnarok\Fenrir\Bitwise\Bitwise;
use Ragnarok\Fenrir\Enums\Intent;
use Tempcord\TempcordConfig;

use function Tempest\env;

return new TempcordConfig(
    token: env('DISCORD_TOKEN'),
    intents: Bitwise::from(
        Intent::GUILD_MESSAGES,
        Intent::DIRECT_MESSAGES,
        Intent::MESSAGE_CONTENT
    )
);
