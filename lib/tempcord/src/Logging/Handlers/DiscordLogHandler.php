<?php

namespace Tempcord\Logging\Handlers;

use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Field;
use Discord\Parts\Guild\Guild;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Tempcord\Tempcord;
use function Tempest\get;
use function Tempest\Support\str;

final class DiscordLogHandler extends AbstractProcessingHandler
{
    private string $guildID;
    private string $channelID;

    public function __construct(string $guildID, string $channelID, $level = Level::Warning, $bubble = true)
    {
        $this->guildID = $guildID;
        $this->channelID = $channelID;
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $tempcord = get(Tempcord::class);

        if (!$tempcord->booted) {
            return;
        }

        $color = match ($record->level) {
            Level::Error, Level::Emergency, Level::Alert => 15158332,
            Level::Warning => 15105570,
            Level::Info, Level::Notice => 3447003,
            default => 3066993
        };

        if (str($record->message)->contains('queued REQ POS')) {
            return;
        }


        $tempcord->discord->guilds->fetch($this->guildID)->then(function (Guild $guild) use ($tempcord, $record, $color) {
            $guild->channels->fetch($this->channelID)->then(function (Channel $channel) use ($tempcord, $record, $color) {

                $embed = new Embed($tempcord->discord)
                    ->setColor($color)
                    ->setDescription($record->message)
                    ->setTimestamp($record->datetime->getTimestamp());

                foreach ($record->context as $item => $value) {
                    $embed->addField(
                        new Field($tempcord->discord, [
                            'name' => $item,
                            'value' => $value,
                        ])
                    );
                }

                foreach ($record->extra as $item => $value) {
                    $embed->addField(
                        new Field($tempcord->discord, [
                            'name' => $item,
                            'value' => $value,
                        ])
                    );
                }

                $channel->sendMessage(
                    MessageBuilder::new()
                        ->addEmbed($embed)
                );
            });
        });
    }
}