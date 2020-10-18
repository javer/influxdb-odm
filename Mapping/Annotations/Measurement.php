<?php

namespace Javer\InfluxDB\ODM\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Class Measurement
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @package Javer\InfluxDB\ODM\Mapping\Annotations
 */
final class Measurement extends Annotation
{
    public ?string $name = null;

    public ?string $repositoryClass = null;
}
