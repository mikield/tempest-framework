<?php

namespace Tempcord;

use Discord\WebSockets\Intents;

final class TempcordConfig
{
    public function __construct(
        public readonly string $token,
        public ?int            $intents = null,
    )
    {
        if (is_null($this->intents)) {
            $this->intents = Intents::getDefaultIntents();
        }
    }
}