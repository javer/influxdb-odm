<?php

namespace Javer\InfluxDB\ODM\Types;

final class FloatType extends Type
{
    public function convertToDatabaseValue(mixed $value): ?float
    {
        return $value !== null ? (float) $value : null;
    }

    public function convertToPHPValue(mixed $value): ?float
    {
        return $value !== null && $value !== 'null' ? (float) $value : null;
    }
}
