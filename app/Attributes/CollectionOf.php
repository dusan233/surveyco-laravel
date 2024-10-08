<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class CollectionOf
{
    public string $classType;

    public function __construct(string $classType)
    {
        $this->classType = $classType;
    }
}
