<?php

namespace Javer\InfluxDB\ODM\Repository;

use DateTimeInterface;
use Doctrine\Persistence\ObjectRepository;
use Javer\InfluxDB\ODM\Mapping\MappingException;
use Javer\InfluxDB\ODM\MeasurementManager;
use Javer\InfluxDB\ODM\Query\Query;

/**
 * @template T of object
 *
 * @template-implements ObjectRepository<T>
 */
class MeasurementRepository implements ObjectRepository
{
    /**
     * Constructor.
     *
     * @phpstan-param class-string<T> $className
     */
    public function __construct(
        private readonly MeasurementManager $measurementManager,
        private readonly string $className,
    )
    {
    }

    /**
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
        if (!$id instanceof DateTimeInterface) {
            return null;
        }

        $classMetadata = $this->measurementManager->getClassMetadata($this->className);

        if ($classMetadata->identifier === null) {
            throw MappingException::missingIdentifierField($this->className);
        }

        return $this->createQuery()
            ->where($classMetadata->identifier, $id)
            ->orderBy($classMetadata->identifier)
            ->getResult()[0] ?? null;
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

    protected function getMeasurementManager(): MeasurementManager
    {
        return $this->measurementManager;
    }
}
