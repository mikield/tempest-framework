<?php

namespace App;

use Tempest\Container\Singleton;

#[Singleton]
final class ArrayMemoryState
{
    private array $items = [];

    public function write(mixed $key, mixed $item): void
    {
        $this->items[$key] = $item;
    }

    public function read(mixed $key): mixed
    {
        return $this->items[$key];
    }

}