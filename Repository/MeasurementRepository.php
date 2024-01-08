<?php

namespace Javer\InfluxDB\ODM\Repository;

use Doctrine\Persistence\ObjectRepository;
use Javer\InfluxDB\ODM\Mapping\MappingException;
use Javer\InfluxDB\ODM\MeasurementManager;
use Javer\InfluxDB\ODM\Query\Query;

/**
 * @template T of object
 * @template-implements ObjectRepository<T>
 */
class MeasurementRepository implements ObjectRepository
{
    private MeasurementManager $measurementManager;

    /**
     * @phpstan-var class-string<T>
     */
    private string $className;

    /**
     * MeasurementRepository constructor.
     *
     * @param MeasurementManager $measurementManager
     * @param string             $className
     *
     * @phpstan-param class-string<T> $className
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
     *
     * @phpstan-return Query<T>
     */
    public function createQuery(): Query
    {
        return new Query($this->measurementManager, $this->className);
    }

    /**
     * {@inheritDoc}
     *
     * @throws MappingException
     *
     * @phpstan-return ?T
     */
    public function find($id)
    {
        if ($id === null) {
            return null;
        }

        $classMetadata = $this->measurementManager->getClassMetadata($this->className);

        if ($classMetadata->identifier === null) {
            throw MappingException::missingIdentifierField($this->className);
        }

        return $this->createQuery()->where($classMetadata->identifier, $id)->getResult()[0] ?? null;
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-return array<int, T>
     */
    public function findAll(): array
    {
        return $this->findBy([]);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     * @param int|null                   $limit
     * @param int|null                   $offset
     *
     * @phpstan-return array<int, T>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
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
     *
     * @phpstan-return ?T
     */
    public function findOneBy(array $criteria): ?object
    {
        return $this->findBy($criteria)[0] ?? null;
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-return class-string<T>
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Returns MeasurementManager.
     *
     * @return MeasurementManager
     */
    protected function getMeasurementManager(): MeasurementManager
    {
        return $this->measurementManager;
    }
}
