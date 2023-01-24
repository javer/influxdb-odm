<?php

namespace Javer\InfluxDB\ODM\Types;

final class BooleanType extends Type
{
    public function convertToDatabaseValue(mixed $value): ?bool
    {
        return $value !== null ? (bool) $value : null;
    }

    public function convertToPHPValue(mixed $value): ?bool
    {
        return $value !== null && $value !== 'null' ? (bool) $value : null;
    }
}
