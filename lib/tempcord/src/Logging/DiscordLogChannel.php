<?php

namespace Tempcord\Logging;

use Monolog\Level;
use Tempcord\Logging\Handlers\DiscordLogHandler;
use Tempest\Log\LogChannel;

final readonly class DiscordLogChannel implements LogChannel
{
    public function __construct(
        private string $guildId,
        private string $channelId
    )
    {
    }

    public function getHandlers(Level $level): array
    {
        return [
            new DiscordLogHandler(
                guildID: $this->guildId,
                channelID: $this->channelId
            ),
        ];
    }

    public function getProcessors(): array
    {
        return [
            /**
             * @TODO: Add user and guild context to the handler.
             *   We need to add current calling user and guid context for DiscordLogHandler so
             *   we will be able to log who and where has made an interaction with our bot.
             */
        ];
    }
}