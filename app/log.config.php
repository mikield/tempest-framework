<?php

declare(strict_types=1);

use Tempcord\Logging\ConsoleLogChannel;
use Tempcord\Logging\DiscordLogChannel;
use Tempest\Log\LogConfig;

return new LogConfig(
    channels: [
        new ConsoleLogChannel(
            except: [
                'sending heartbeat',
                'received heartbeat',
                'http not checking',
                'resetting payload count',
            ],
        ),
        new DiscordLogChannel(
            guildId: '932734827545903104',
            channelId: '996457577338638376',
        ),
    ],
);
