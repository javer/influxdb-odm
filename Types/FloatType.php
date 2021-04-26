<?php

namespace Javer\InfluxDB\ODM\Types;

class FloatType extends Type
{
    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValue($value): ?float
    {
        return $value !== null ? (float) $value : null;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value): ?float
    {
        return $value !== null && $value !== 'null' ? (float) $value : null;
    }
}
