<?php

namespace Javer\InfluxDB\ODM\Mapping\Attributes;

use Attribute;
use Javer\InfluxDB\ODM\Types\TypeEnum;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Timestamp extends Field
{
    public function __construct()
    {
        parent::__construct('_time', TypeEnum::TIMESTAMP, false);
    }
}
