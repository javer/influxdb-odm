<?php

namespace Javer\InfluxDB\ODM\Mapping\Attributes;

use Attribute;
use Javer\InfluxDB\ODM\Types\TypeEnum;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Field implements MappingAttributeInterface
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?TypeEnum $type = null,
        public readonly ?bool $countable = null,
    )
    {
    }
}
