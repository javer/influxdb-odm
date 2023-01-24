<?php

namespace Javer\InfluxDB\ODM\Mapping\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Measurement implements MappingAttributeInterface
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $repositoryClass = null,
    )
    {
    }
}
