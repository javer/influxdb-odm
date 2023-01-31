<?php

namespace Javer\InfluxDB\ODM;

final class MeasurementNotFoundException extends InfluxDBException
{
    /**
     * Create from className and identifier.
     *
     * @phpstan-param class-string $className
     */
    public static function fromClassNameAndIdentifier(string $className, mixed $identifier): self
    {
        return new self(sprintf(
            'The "%s" measurement with identifier %s could not be found.',
            $className,
            (string) $identifier
        ));
    }
}
