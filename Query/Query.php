<?php

namespace Javer\InfluxDB\ODM\Query;

use InfluxDB\Query\Builder;
use IteratorAggregate;
use Javer\InfluxDB\ODM\Iterator\HydratingIterator;
use Javer\InfluxDB\ODM\Iterator\IteratorInterface;
use Javer\InfluxDB\ODM\Iterator\UnrewindableIterator;
use Javer\InfluxDB\ODM\Mapping\ClassMetadata;
use Javer\InfluxDB\ODM\MeasurementManager;

/**
 * Class Query
 *
 * @package Javer\InfluxDB\ODM\Query
 */
class Query implements IteratorAggregate
{
    /**
     * Skip hydration, return plain result.
     */
    public const HYDRATE_NONE = 0;

    /**
     * Hydrates an object graph. This is the default behavior.
     */
    public const HYDRATE_OBJECT = 1;

    /**
     * Hydrates an array graph.
     */
    public const HYDRATE_ARRAY = 2;

    /**
     * Hydrates a flat, rectangular result set with scalar values.
     */
    public const HYDRATE_SCALAR = 3;

    /**
     * Hydrates a single scalar value.
     */
    public const HYDRATE_SINGLE_SCALAR = 4;

    private MeasurementManager $measurementManager;

    private ClassMetadata $classMetadata;

    private Builder $queryBuilder;

    private string $className;

    private int $hydrationMode = self::HYDRATE_OBJECT;

    private ?IteratorInterface $iterator = null;

    /**
     * Query constructor.
     *
     * @param MeasurementManager $measurementManager
     * @param string             $className
     */
    public function __construct(MeasurementManager $measurementManager, string $className)
    {
        $this->measurementManager = $measurementManager;
        $this->className = $className;
        $this->classMetadata = $measurementManager->getClassMetadata($className);
        $this->queryBuilder = $measurementManager->getDatabase()->getQueryBuilder();
        $this->queryBuilder->from($measurementManager->getClassMetadata($className)->getMeasurement());
    }

    /**
     * Clones the object.
     */
    public function __clone()
    {
        $this->queryBuilder = clone $this->queryBuilder;
        $this->iterator = null;
    }

    /**
     * Returns queryBuilder.
     *
     * @return Builder
     */
    public function getQueryBuilder(): Builder
    {
        return $this->queryBuilder;
    }

    /**
     * Returns className.
     *
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Returns classMetadata.
     *
     * @return ClassMetadata
     */
    public function getClassMetadata(): ClassMetadata
    {
        return $this->classMetadata;
    }

    /**
     * Set hydrationMode.
     *
     * @param integer $hydrationMode One of the Query::HYDRATE_* constants.
     *
     * @return Query
     */
    public function setHydrationMode(int $hydrationMode): Query
    {
        $this->hydrationMode = $hydrationMode;

        return $this;
    }

    /**
     * Returns hydrationMode.
     *
     * @return integer
     */
    public function getHydrationMode(): int
    {
        return $this->hydrationMode;
    }

    /**
     * Gets the raw results for the query.
     *
     * @return array
     */
    public function getRawResult(): array
    {
        return $this->execute(self::HYDRATE_NONE);
    }

    /**
     * Gets the list of results for the query.
     *
     * @return array
     */
    public function getResult(): array
    {
        return $this->execute(self::HYDRATE_OBJECT);
    }

    /**
     * Gets the array of results for the query.
     *
     * @return array
     */
    public function getArrayResult(): array
    {
        return $this->execute(self::HYDRATE_ARRAY);
    }

    /**
     * Gets the scalar results for the query.
     *
     * @return array
     */
    public function getScalarResult(): array
    {
        return $this->execute(self::HYDRATE_SCALAR);
    }

    /**
     * Gets the single scalar result.
     *
     * @return mixed|null
     */
    public function getSingleScalarResult()
    {
        $result = $this->execute(self::HYDRATE_SINGLE_SCALAR);

        return count($result) > 0 ? array_shift($result) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): ?IteratorInterface
    {
        return $this->iterator;
    }

    /**
     * Executes the query and returns hydrated or plain result set.
     *
     * @param integer|null $hydrationMode
     *
     * @return array
     */
    public function execute(int $hydrationMode = null): array
    {
        return $this->iterate($hydrationMode)->toArray();
    }

    /**
     * Iterate the results.
     *
     * @param integer|null $hydrationMode
     *
     * @return IteratorInterface
     */
    public function iterate(int $hydrationMode = null): IteratorInterface
    {
        if ($hydrationMode !== null) {
            $this->setHydrationMode($hydrationMode);
        }

        $cursor = $this->queryBuilder->getResultSet()->getPoints();

        if ($this->hydrationMode !== self::HYDRATE_NONE) {
            $hydrator = $this->measurementManager->createHydrator($this->className, $this->hydrationMode);

            $cursor = new HydratingIterator($cursor, $hydrator);
        }

        return $this->iterator = new UnrewindableIterator($cursor);
    }

    /**
     * Select.
     *
     * @param string $select
     *
     * @return self
     */
    public function select(string $select): self
    {
        $this->queryBuilder->select($select);

        return $this;
    }

    /**
     * Where.
     *
     * @param string $condition
     * @param mixed  $value
     *
     * @return self
     */
    public function where(string $condition, $value = null): self
    {
        if ($value !== null) {
            $fieldName = $condition;

            $condition = sprintf(
                "%s = '%s'",
                $this->classMetadata->getFieldDatabaseName($fieldName),
                addslashes($this->classMetadata->getFieldDatabaseValue($fieldName, $value))
            );
        }

        $this->queryBuilder->where([$condition]);

        return $this;
    }

    /**
     * Count.
     *
     * @param string $fieldName
     *
     * @return self
     */
    public function count(string $fieldName): self
    {
        $this->queryBuilder->count($this->classMetadata->getFieldDatabaseName($fieldName));

        return $this;
    }

    /**
     * Median.
     *
     * @param string $fieldName
     *
     * @return self
     */
    public function median(string $fieldName): self
    {
        $this->queryBuilder->median($this->classMetadata->getFieldDatabaseName($fieldName));

        return $this;
    }

    /**
     * Mean.
     *
     * @param string $fieldName
     *
     * @return self
     */
    public function mean(string $fieldName): self
    {
        $this->queryBuilder->mean($this->classMetadata->getFieldDatabaseName($fieldName));

        return $this;
    }

    /**
     * Sum.
     *
     * @param string $fieldName
     *
     * @return self
     */
    public function sum(string $fieldName): self
    {
        $this->queryBuilder->sum($this->classMetadata->getFieldDatabaseName($fieldName));

        return $this;
    }

    /**
     * First.
     *
     * @param string $fieldName
     *
     * @return self
     */
    public function first(string $fieldName): self
    {
        $this->queryBuilder->first($this->classMetadata->getFieldDatabaseName($fieldName));

        return $this;
    }

    /**
     * Last.
     *
     * @param string $fieldName
     *
     * @return self
     */
    public function last(string $fieldName): self
    {
        $this->queryBuilder->last($this->classMetadata->getFieldDatabaseName($fieldName));

        return $this;
    }

    /**
     * Group by.
     *
     * @param string  $field
     * @param boolean $mappedField
     *
     * @return self
     */
    public function groupBy(string $field, bool $mappedField = true): self
    {
        $this->queryBuilder->groupBy($mappedField ? $this->classMetadata->getFieldDatabaseName($field) : $field);

        return $this;
    }

    /**
     * Order by.
     *
     * @param string $field
     * @param string $direction
     *
     * @return self
     */
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->queryBuilder->orderBy($this->classMetadata->getFieldDatabaseName($field), $direction);

        return $this;
    }

    /**
     * Offset.
     *
     * @param integer $offset
     *
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->queryBuilder->offset($offset);

        return $this;
    }

    /**
     * Limit.
     *
     * @param integer $limit
     *
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->queryBuilder->limit($limit);

        return $this;
    }
}
