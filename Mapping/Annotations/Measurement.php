<?php

namespace Javer\InfluxDB\ODM\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Measurement extends Annotation
{
    public ?string $name = null;

    public ?string $repositoryClass = null;
}
