<?php

namespace Javer\InfluxDB\ODM\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;
use InfluxDB\Database;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Timestamp extends Field
{
    public ?string $name = 'time';

    public ?string $type = 'timestamp';

    public string $precision = Database::PRECISION_MICROSECONDS;

    public bool $id = true;
}
