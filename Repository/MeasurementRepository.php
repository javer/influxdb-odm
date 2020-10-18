<?php

namespace Javer\InfluxDB\ODM\Repository;

use Doctrine\Persistence\ObjectRepository;
use Javer\InfluxDB\ODM\MeasurementManager;
use Javer\InfluxDB\ODM\Query\Query;

/**
 * Class MeasurementRepository
 *
 * @package Javer\InfluxDB\ODM\Repository
 */
class MeasurementRepository implements ObjectRepository
{
    private MeasurementManager $measurementManager;

    private string $className;

    /**
     * MeasurementRepository constructor.
     *
     * @param MeasurementManager $measurementManager
     * @param string             $className
     */
    public function __construct(MeasurementManager $measurementManager, string $className)
    {
        $this->measurementManager = $measurementManager;
        $this->className = $className;
    }

    /**
     * Create a new query.
     *
     * @return Query
     */
    public function createQuery(): Query
    {
        return new Query($this->measurementManager, $this->className);
    }

    /**
     * {@inheritDoc}
     */
    public function find($id)
    {
        if ($id === null) {
            return null;
        }

        $classMetadata = $this->measurementManager->getClassMetadata($this->className);

        return $this->createQuery()->where($classMetadata->identifier, $id)->getResult()[0] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(): array
    {
        return $this->findBy([]);
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
        $query = $this->createQuery();

        foreach ($criteria as $fieldName => $value) {
            $query->where($fieldName, $value);
        }

        if ($orderBy !== null) {
            $query->orderBy(...$orderBy);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        return $query->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findOneBy(array $criteria): ?object
    {
        return $this->findBy($criteria)[0] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function getClassName(): string
    {
        return $this->className;
    }
}
