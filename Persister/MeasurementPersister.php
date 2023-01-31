<?php

namespace Javer\InfluxDB\ODM\Persister;

use InfluxDB2\Point;
use Javer\InfluxDB\ODM\MeasurementManager;
use Javer\InfluxDB\ODM\Model\DeletionPoint;
use Javer\InfluxDB\ODM\Types\Type;

final class MeasurementPersister
{
    public function __construct(
        private readonly MeasurementManager $measurementManager,
    )
    {
    }

    /**
     * Persist objects to the database.
     *
     * @param iterable $objects
     *
     * @phpstan-param iterable<object> $objects
     */
    public function persist(iterable $objects): void
    {
        $points = [];

        foreach ($objects as $object) {
            $points[] = $this->mapObjectToPoint($object);
        }

        $client = $this->measurementManager->getClient();
        $client->write($points);
    }

    /**
     * Remove object from the database.
     */
    public function remove(object $object): void
    {
        $client = $this->measurementManager->getClient();
        $client->delete([$this->mapToDeletionPoint($object)]);
    }

    private function mapObjectToPoint(object $object): Point
    {
        $classMetadata = $this->measurementManager->getClassMetadata($object::class);

        $tags = [];
        $fields = [];
        $timestamp = null;

        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $fieldValue = $classMetadata->getFieldValue($object, $fieldName);

            if ($classMetadata->identifier === $fieldName) {
                $timestamp = (int) $fieldValue->format('Uu000');

                continue;
            }

            $fieldMapping = $classMetadata->getFieldMapping($fieldName);
            $name = $fieldMapping['name'];
            $value = Type::getType($fieldMapping['type'])->convertToDatabaseValue($fieldValue);

            if ($fieldMapping['tag'] ?? false) {
                if ($value !== null) {
                    $tags[$name] = (string) $value;
                }
            } elseif ($value !== null) {
                $fields[$name] = $value;
            }
        }

        return new Point($classMetadata->getMeasurement(), $tags, $fields, $timestamp);
    }

    private function mapToDeletionPoint(object $object): DeletionPoint
    {
        $classMetadata = $this->measurementManager->getClassMetadata($object::class);

        $tags = [];
        $timestamp = null;

        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $fieldValue = $classMetadata->getFieldValue($object, $fieldName);

            if ($classMetadata->identifier === $fieldName) {
                $timestamp = $fieldValue->format('U.u');

                continue;
            }

            $fieldMapping = $classMetadata->getFieldMapping($fieldName);

            if ($fieldMapping['tag'] ?? false) {
                $value = Type::getType($fieldMapping['type'])->convertToDatabaseValue($fieldValue);

                if ($value !== null) {
                    $tags[$fieldMapping['name']] = (string) $value;
                }
            }
        }

        return new DeletionPoint($classMetadata->getMeasurement(), (float) $timestamp, $tags);
    }
}
