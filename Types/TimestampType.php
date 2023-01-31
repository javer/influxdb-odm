<?php

namespace Javer\InfluxDB\ODM\Types;

use DateTime;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;

final class TimestampType extends Type
{
    private const RFC3339_MICROSECONDS = 'Y-m-d\TH:i:s.uP';

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     */
    public function convertToDatabaseValue(mixed $value): float|string|null
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
    public function convertToPHPValue(mixed $value): ?DateTimeInterface
    {
        if ($value === null || $value instanceof DateTimeInterface) {
            return $value;
        }

        try {
            $value = new DateTime($value);
        } catch (Exception) {
            throw new InvalidArgumentException(
                sprintf(
                    'Could not convert %s to a date value',
                    is_scalar($value) ? '"' . $value . '"' : gettype($value)
                )
            );
        }

        return $value;
    }
}
