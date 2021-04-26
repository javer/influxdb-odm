<?php

namespace Javer\InfluxDB\ODM\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Field extends Annotation
{
    public ?string $name = null;

    public ?string $type = null;

    public ?bool $countable = null;
}
