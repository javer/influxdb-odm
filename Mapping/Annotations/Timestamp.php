<?php

namespace Javer\InfluxDB\ODM\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;
use InfluxDB\Database;

/**
 * Class Timestamp
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @package Javer\InfluxDB\ODM\Mapping\Annotations
 */
final class Timestamp extends Field
{
    public ?string $name = 'time';

    public ?string $type = 'timestamp';

    public string $precision = Database::PRECISION_MICROSECONDS;

    public bool $id = true;
}
