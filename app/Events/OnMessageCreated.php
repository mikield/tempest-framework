<?php

namespace App\Events;

use Ragnarok\Fenrir\Constants\Events;
use Ragnarok\Fenrir\Gateway\Events\MessageCreate;
use Ragnarok\Fenrir\Rest\Helpers\Channel\MessageBuilder;
use Tempcord\Attributes\Event;
use Tempcord\Tempcord;

#[Event(
    name: Events::MESSAGE_CREATE
)]
readonly class OnMessageCreated
{
    public function __construct(
        private Tempcord $tempcord
    )
    {

    }

    public function __invoke(MessageCreate $event): void
    {
        if (isset($event->author->bot) && $event->author->bot) return;
        $this->tempcord->discord->rest->channel->createMessage(996457577338638376, MessageBuilder::new()
            ->setContent('New message: ' . $event->content));
    }

}