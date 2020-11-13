<?php

namespace Javer\InfluxDB\ODM\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Class Field
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @package Javer\InfluxDB\ODM\Mapping\Annotations
 */
class Field extends Annotation
{
    public ?string $name = null;

    public ?string $type = null;

    public ?bool $countable = null;
}
