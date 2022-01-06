<?php

namespace Javer\InfluxDB\ODM\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use InfluxDB\Database;

/**
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Timestamp extends Field
{
    public string $precision = Database::PRECISION_MICROSECONDS;

    public bool $id = true;

    public function __construct(
        ?string $name = 'time',
        ?string $type = 'timestamp',
        string $precision = Database::PRECISION_MICROSECONDS,
    )
    {
        parent::__construct($name, $type);

        $this->precision = $precision;
    }
}
