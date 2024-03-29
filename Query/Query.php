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
 * @template T of object
 * @template-implements IteratorAggregate<T>
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

    /**
     * @phpstan-var ClassMetadata<T>
     */
    private ClassMetadata $classMetadata;

    private Builder $queryBuilder;

    /**
     * @phpstan-var class-string<T>
     */
    private string $className;

    private int $hydrationMode = self::HYDRATE_OBJECT;

    /**
     * @phpstan-var IteratorInterface<T>|null
     */
    private ?IteratorInterface $iterator = null;

    /**
     * Query constructor.
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
     *
     * @phpstan-return class-string<T>
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Returns classMetadata.
     *
     * @return ClassMetadata
     *
     * @phpstan-return ClassMetadata<T>
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
     * @return self
     *
     * @phpstan-return Query<T>
     */
    public function setHydrationMode(int $hydrationMode): self
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
     * @return array<int, mixed>
     */
    public function getRawResult(): array
    {
        return $this->execute(self::HYDRATE_NONE);
    }

    /**
     * Gets the list of results for the query.
     *
     * @return array<int, T>
     */
    public function getResult(): array
    {
        return $this->execute(self::HYDRATE_OBJECT);
    }

    /**
     * Gets the array of results for the query.
     *
     * @return array<int, mixed>
     */
    public function getArrayResult(): array
    {
        return $this->execute(self::HYDRATE_ARRAY);
    }

    /**
     * Gets the scalar results for the query.
     *
     * @return array<int, mixed>
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
     *
     * @phpstan-return IteratorInterface<T>
     */
    public function getIterator(): IteratorInterface
    {
        return $this->iterator ?? $this->iterate();
    }

    /**
     * Executes the query and returns hydrated or plain result set.
     *
     * @param integer|null $hydrationMode
     *
     * @return array<int, mixed>
     */
    public function execute(int $hydrationMode = null): array
    {
        return $this->iterate($hydrationMode)->toArray();
    }

    /**
     * Executes COUNT query and returns number of records.
     *
     * @return integer
     */
    public function executeCount(): int
    {
        return $this->count($this->classMetadata->getCountableFieldName())->getSingleScalarResult() ?? 0;
    }

    /**
     * Iterate the results.
     *
     * @param integer|null $hydrationMode
     *
     * @return IteratorInterface
     *
     * @phpstan-return IteratorInterface<T>
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
     * Quote field name.
     *
     * @param string  $fieldName
     * @param boolean $mappedField
     * @param boolean $enquote
     *
     * @return string
     */
    public function quoteFieldName(string $fieldName, bool $mappedField = true, bool $enquote = true): string
    {
        if (!$mappedField) {
            return $fieldName;
        }

        $fieldName = $this->classMetadata->getFieldDatabaseName($fieldName);

        return $enquote ? sprintf('"%s"', addslashes($fieldName)) : $fieldName;
    }

    /**
     * Select.
     *
     * @param string $select
     *
     * @return self
     *
     * @phpstan-return Query<T>
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
     *
     * @phpstan-return Query<T>
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
     * @param string  $fieldName
     * @param boolean $mappedField
     *
     * @return self
     *
     * @phpstan-return Query<T>
     */
    public function count(string $fieldName, bool $mappedField = true): self
    {
        $this->queryBuilder->count($this->quoteFieldName($fieldName, $mappedField));

        return $this;
    }

    /**
     * Median.
     *
     * @param string  $fieldName
     * @param boolean $mappedField
     *
     * @return self
     *
     * @phpstan-return Query<T>
     */
    public function median(string $fieldName, bool $mappedField = true): self
    {
        $this->queryBuilder->median($this->quoteFieldName($fieldName, $mappedField));

        return $this;
    }

    /**
     * Mean.
     *
     * @param string  $fieldName
     * @param boolean $mappedField
     *
     * @return self
     *
     * @phpstan-return Query<T>
     */
    public function mean(string $fieldName, bool $mappedField = true): self
    {
        $this->queryBuilder->mean($this->quoteFieldName($fieldName, $mappedField));

        return $this;
    }

    /**
     * Sum.
     *
     * @param string  $fieldName
     * @param boolean $mappedField
     *
     * @return self
     *
     * @phpstan-return Query<T>
     */
    public function sum(string $fieldName, bool $mappedField = true): self
    {
        $this->queryBuilder->sum($this->quoteFieldName($fieldName, $mappedField));

        return $this;
    }

    /**
     * First.
     *
     * @param string  $fieldName
     * @param boolean $mappedField
     *
     * @return self
     *
     * @phpstan-return Query<T>
     */
    public function first(string $fieldName, bool $mappedField = true): self
    {
        $this->queryBuilder->first($this->quoteFieldName($fieldName, $mappedField));

        return $this;
    }

    /**
     * Last.
     *
     * @param string  $fieldName
     * @param boolean $mappedField
     *
     * @return self
     *
     * @phpstan-return Query<T>
     */
    public function last(string $fieldName, bool $mappedField = true): self
    {
        $this->queryBuilder->last($this->quoteFieldName($fieldName, $mappedField));

        return $this;
    }

    /**
     * Group by.
     *
     * @param string  $field
     * @param boolean $mappedField
     *
     * @return self
     *
     * @phpstan-return Query<T>
     */
    public function groupBy(string $field, bool $mappedField = true): self
    {
        $this->queryBuilder->groupBy($this->quoteFieldName($field, $mappedField));

        return $this;
    }

    /**
     * Order by.
     *
     * @param string  $field
     * @param string  $direction
     * @param boolean $mappedField
     *
     * @return self
     *
     * @phpstan-return Query<T>
     */
    public function orderBy(string $field, string $direction = 'ASC', bool $mappedField = true): self
    {
        $this->queryBuilder->orderBy($this->quoteFieldName($field, $mappedField, false), $direction);

        return $this;
    }

    /**
     * Offset.
     *
     * @param integer $offset
     *
     * @return self
     *
     * @phpstan-return Query<T>
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
     *
     * @phpstan-return Query<T>
     */
    public function limit(int $limit): self
    {
        $this->queryBuilder->limit($limit);

        return $this;
    }
}
