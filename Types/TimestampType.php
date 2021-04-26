<?php

namespace Javer\InfluxDB\ODM\Types;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

class TimestampType extends Type
{
    private const RFC3339 = 'Y-m-d\TH:i:sP';
    private const RFC3339_MICROSECONDS = 'Y-m-d\TH:i:s.uP';
    private const TIMEZONE = 'UTC';

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     */
    public function convertToDatabaseValue($value)
    {
        if ($value === null || is_float($value) || is_string($value)) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return str_replace('+00:00', 'Z', $value->format(self::RFC3339_MICROSECONDS));
        }

        throw new InvalidArgumentException(
            sprintf('Could not convert %s to a date value', is_scalar($value) ? '"' . $value . '"' : gettype($value))
        );
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     */
    public function convertToPHPValue($value)
    {
        if ($value === null || $value instanceof DateTimeInterface) {
            return $value;
        }

        $val = DateTime::createFromFormat(self::RFC3339_MICROSECONDS, $value, new DateTimeZone(self::TIMEZONE));

        if ($val === false) {
            $val = DateTime::createFromFormat(self::RFC3339, $value, new DateTimeZone(self::TIMEZONE));
        }

        if ($val === false) {
            throw new InvalidArgumentException(
                sprintf(
                    'Could not convert %s to a date value',
                    is_scalar($value) ? '"' . $value . '"' : gettype($value)
                )
            );
        }

        return $val;
    }
}
