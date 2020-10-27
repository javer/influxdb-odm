<?php

namespace Javer\InfluxDB\ODM;

/**
 * Class MeasurementNotFoundException
 *
 * @package Javer\InfluxDB\ODM
 */
class MeasurementNotFoundException extends InfluxDBException
{
    /**
     * Create from className and identifier.
     *
     * @param string $className
     * @param mixed  $identifier
     *
     * @return self
     */
    public static function fromClassNameAndIdentifier(string $className, $identifier): self
    {
        return new self(sprintf(
            'The "%s" measurement with identifier %s could not be found.',
            $className,
            (string) $identifier
        ));
    }
}
