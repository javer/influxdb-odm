<?php

namespace Javer\InfluxDB\ODM\Types;

class BooleanType extends Type
{
    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValue($value): ?bool
    {
        return $value !== null ? (bool) $value : null;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value): ?bool
    {
        return $value !== null && $value !== 'null' ? (bool) $value : null;
    }
}
