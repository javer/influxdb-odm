<?php

namespace Javer\InfluxDB\ODM\Mapping\Attributes;

use Attribute;
use Javer\InfluxDB\ODM\Types\TypeEnum;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Tag extends Field
{
    public readonly bool $tag;

    public function __construct(?string $name = null, ?TypeEnum $type = null)
    {
        parent::__construct($name, $type, false);

        $this->tag = true;
    }
}
