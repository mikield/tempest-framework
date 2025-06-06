<?php

namespace Tempcord\Attributes;

use Attribute;
use BackedEnum;
use Tempcord\Traits\HasAttributes;

#[Attribute(Attribute::TARGET_CLASS)]
final  class SubcommandGroup
{
    use HasAttributes;

    public string $name {
        get {
            $name = $this->getAttribute('name');
            return $name instanceof BackedEnum ? $name->value : $name;
        }
    }


    public function __construct(
        string|BackedEnum $name,
        public string     $description
    )
    {
        $this->setAttribute('name', $name);
    }

}