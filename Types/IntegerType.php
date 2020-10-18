<?php

namespace Javer\InfluxDB\ODM\Types;

/**
 * Class IntegerType
 *
 * @package Javer\InfluxDB\ODM\Types
 */
class IntegerType extends Type
{
    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValue($value): ?int
    {
        return $value !== null ? (int) $value : null;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value): ?int
    {
        return $value !== null && $value !== 'null' ? (int) $value : null;
    }
}
