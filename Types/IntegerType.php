<?php

namespace Javer\InfluxDB\ODM\Types;

final class IntegerType extends Type
{
    public function convertToDatabaseValue(mixed $value): ?int
    {
        return $value !== null ? (int) $value : null;
    }

    public function convertToPHPValue(mixed $value): ?int
    {
        return $value !== null && $value !== 'null' ? (int) $value : null;
    }
}
