<?php

namespace Javer\InfluxDB\ODM\Types;

/**
 * Class StringType
 *
 * @package Javer\InfluxDB\ODM\Types
 */
class StringType extends Type
{
    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValue($value): ?string
    {
        return $value !== null ? (string) $value : null;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value): ?string
    {
        return $value !== null && $value !== 'null' ? (string) $value : null;
    }
}
