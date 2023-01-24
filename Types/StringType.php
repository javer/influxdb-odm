<?php

namespace Javer\InfluxDB\ODM\Types;

final class StringType extends Type
{
    public function convertToDatabaseValue(mixed $value): ?string
    {
        return $value !== null ? (string) $value : null;
    }

    public function convertToPHPValue(mixed $value): ?string
    {
        return $value !== null && $value !== 'null' ? (string) $value : null;
    }
}
