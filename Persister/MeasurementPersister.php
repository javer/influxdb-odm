<?php

namespace Javer\InfluxDB\ODM\Persister;

use InfluxDB\Database;
use InfluxDB\Point;
use Javer\InfluxDB\ODM\MeasurementManager;
use Javer\InfluxDB\ODM\Types\Type;

/**
 * Class MeasurementPersister
 *
 * @package Javer\InfluxDB\ODM\Persister
 */
class MeasurementPersister
{
    private const PRECISION_MULTIPLIERS = [
        Database::PRECISION_NANOSECONDS => 10 ** 9,
        Database::PRECISION_MICROSECONDS => 10 ** 6,
        Database::PRECISION_MILLISECONDS => 10 ** 3,
        Database::PRECISION_SECONDS => 1,
        Database::PRECISION_MINUTES => 1 / 60,
        Database::PRECISION_HOURS => 1 / 3600,
    ];

    private MeasurementManager $measurementManager;

    private string $precision;

    /**
     * MeasurementPersister constructor.
     *
     * @param MeasurementManager $measurementManager
     */
    public function __construct(MeasurementManager $measurementManager)
    {
        $this->measurementManager = $measurementManager;
    }

    /**
     * Persist objects to the database.
     *
     * @param iterable $objects
     */
    public function persist(iterable $objects): void
    {
        $points = [];

        foreach ($objects as $object) {
            $points[] = $this->mapObjectToPoint($object);
        }

        $this->measurementManager->getDatabase()->writePoints($points, $this->precision);
    }

    /**
     * Remove object from the database.
     *
     * @param object $object
     */
    public function remove(object $object): void
    {
        $classMetadata = $this->measurementManager->getClassMetadata(get_class($object));

        $tags = [];
        $timestamp = null;

        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $fieldMapping = $classMetadata->getFieldMapping($fieldName);
            $fieldValue = $classMetadata->getFieldValue($object, $fieldName);
            $type = $fieldMapping['type'];
            $name = $fieldMapping['name'];
            $value = Type::getType($type)->convertToDatabaseValue($fieldValue);

            if ($fieldMapping['id'] ?? false) {
                $timestamp = $value;
            } elseif ($fieldMapping['tag'] ?? false) {
                $tags[$name] = $value;
            }
        }

        $query = sprintf("DELETE FROM %s WHERE time = '%s'", $classMetadata->getMeasurement(), $timestamp);

        foreach ($tags as $tagName => $tagValue) {
            $query .= sprintf(" AND %s = '%s'", $tagName, addslashes($tagValue));
        }

        $this->measurementManager->getDatabase()->query($query);
    }

    /**
     * Map object to point.
     *
     * @param object $object
     *
     * @return Point
     */
    private function mapObjectToPoint(object $object): Point
    {
        $classMetadata = $this->measurementManager->getClassMetadata(get_class($object));

        $tags = [];
        $fields = [];
        $timestamp = null;

        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $fieldMapping = $classMetadata->getFieldMapping($fieldName);
            $fieldValue = $classMetadata->getFieldValue($object, $fieldName);
            $type = $fieldMapping['type'];
            $name = $fieldMapping['name'];
            $value = Type::getType($type)->convertToDatabaseValue($fieldValue);

            if ($fieldMapping['id'] ?? false) {
                $this->precision = $fieldMapping['precision'];

                $timestamp = sprintf('%d', $fieldValue->format('U.u') * self::PRECISION_MULTIPLIERS[$this->precision]);
            } elseif ($fieldMapping['tag'] ?? false) {
                if ($value !== null) {
                    $tags[$name] = $value;
                }
            } else {
                $fields[$name] = $value;
            }
        }

        return new Point($classMetadata->getMeasurement(), null, $tags, $fields, $timestamp);
    }
}
