<?php

namespace Javer\InfluxDB\ODM\Query;

use DateTime;
use DateTimeInterface;
use IteratorAggregate;
use Javer\InfluxDB\ODM\InfluxDBException;
use Javer\InfluxDB\ODM\Iterator\HydratingIterator;
use Javer\InfluxDB\ODM\Iterator\IteratorInterface;
use Javer\InfluxDB\ODM\Iterator\UnrewindableIterator;
use Javer\InfluxDB\ODM\Mapping\ClassMetadata;
use Javer\InfluxDB\ODM\MeasurementManager;

/**
 * @template T of object
 *
 * @template-implements IteratorAggregate<T>
 */
final class Query implements IteratorAggregate
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

    /**
     * @phpstan-var ClassMetadata<T>
     */
    private readonly ClassMetadata $classMetadata;

    private int $hydrationMode = self::HYDRATE_OBJECT;

    /**
     * @phpstan-var IteratorInterface<T>|null
     */
    private ?IteratorInterface $iterator = null;

    /**
     * @var string[]
     */
    private array $select = [];

    /**
     * @var string[]
     */
    private array $where = [];

    private string $from;

    /**
     * @var string[]
     *
     * @phpstan-var array<int, array{field: string, desc: bool}>
     */
    private array $orderBy = [];

    /**
     * @var string[]
     */
    private array $groupBy = [];

    private int $offset = 0;

    private int $limit = 0;

    private int $dateFrom = 0;

    private int $dateTo;

    private ?AggregateEnum $aggregate = null;

    private ?string $aggregateField = null;

    private ?string $aggregatePeriod = null;

    /**
     * @var string[]
     */
    private array $imports = [];

    /**
     * @var mixed[]
     *
     * @phpstan-var array<string, mixed>
     */
    private array $fills = [];

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
        $this->classMetadata = $measurementManager->getClassMetadata($className);
        $this->from = $this->classMetadata->getMeasurement();
        $this->dateTo = (int) (new DateTime())->format('Uu000');
    }

    public function __clone()
    {
        $this->iterator = null;
    }

    /**
     * Returns className.
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
     * @phpstan-return ClassMetadata<T>
     */
    public function getClassMetadata(): ClassMetadata
    {
        return $this->classMetadata;
    }

    /**
     * Set hydrationMode.
     *
     * @param int $hydrationMode One of the Query::HYDRATE_* constants.
     *
     * @phpstan-return Query<T>
     */
    public function setHydrationMode(int $hydrationMode): self
    {
        $this->hydrationMode = $hydrationMode;

        return $this;
    }

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
        $table = $this->measurementManager->getClient()->query($this->getFluxQuery());

        $result = [];

        if ($table === null) {
            return $result;
        }

        foreach ($table->records as $record) {
            $result[] = $record->values;
        }

        return $result;
    }

    /**
     * @return mixed[]
     *
     * @phpstan-return array<int, array{time: DateTime, value: int|float}>
     *
     * @throws InfluxDBException
     */
    public function aggregateWindow(
        AggregateEnum $aggregate,
        string $aggregatePeriod,
        ?string $aggregateField = null,
    ): array
    {
        $this->aggregate = $aggregate;
        $this->aggregatePeriod = $aggregatePeriod;
        $this->aggregateField = $aggregateField ?? $this->classMetadata->getCountableFieldName();

        return array_map(static fn(array $item): array => [
            'time' => new DateTime($item['_time']
                ?? throw new InfluxDBException('Missing "_time" field in result.')),
            'value' => $item['_value']
                ?? throw new InfluxDBException('Missing "_value" field in result.'),
        ], $this->getRawResult());
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

    public function getSingleScalarResult(): mixed
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
     * @return array<int, mixed>
     */
    public function execute(?int $hydrationMode = null): array
    {
        return $this->iterate($hydrationMode)->toArray();
    }

    /**
     * Iterate the results.
     *
     * @phpstan-return IteratorInterface<T>
     */
    public function iterate(?int $hydrationMode = null): IteratorInterface
    {
        $cursor = $this->getRawResult();

        if ($hydrationMode !== self::HYDRATE_NONE) {
            $hydrator = $this->measurementManager->createHydrator(
                $this->className,
                    $hydrationMode ?? self::HYDRATE_OBJECT,
            );

            $cursor = new HydratingIterator($cursor, $hydrator);
        }

        return $this->iterator = new UnrewindableIterator($cursor);
    }

    /**
     * @param string[] $select
     *
     * @phpstan-return Query<T>
     */
    public function select(array $select): self
    {
        $this->select = $select;

        return $this;
    }

    /**
     * @phpstan-return Query<T>
     */
    public function addSelect(string $select): self
    {
        $this->select[] = $select;

        return $this;
    }

    /**
     * @phpstan-return Query<T>
     */
    public function where(string $condition, mixed $value = null): self
    {
        if ($value !== null) {
            $fieldName = $condition;

            if ($this->classMetadata->isIdentifier($fieldName) && $value instanceof DateTimeInterface) {
                return $this->processIdWhere($value);
            }

            $condition = sprintf(
                'r.%s == "%s"',
                $this->classMetadata->getFieldDatabaseName($fieldName),
                addslashes($this->classMetadata->getFieldDatabaseValue($fieldName, $value))
            );
        }

        $this->where[] = $condition;

        return $this;
    }

    /**
     * @phpstan-return Query<T>
     */
    public function count(): self
    {
        $this->aggregate = AggregateEnum::COUNT;
        $this->aggregateField = $this->classMetadata->getCountableFieldName();

        return $this;
    }

    /**
     * @phpstan-return Query<T>
     */
    public function median(string $fieldName): self
    {
        $this->aggregate = AggregateEnum::MEDIAN;
        $this->aggregateField = $fieldName;

        return $this;
    }

    /**
     * @phpstan-return Query<T>
     */
    public function mean(string $fieldName): self
    {
        $this->aggregate = AggregateEnum::MEAN;
        $this->aggregateField = $fieldName;

        return $this;
    }

    /**
     * @phpstan-return Query<T>
     */
    public function sum(string $fieldName): self
    {
        $this->aggregate = AggregateEnum::SUM;
        $this->aggregateField = $fieldName;

        return $this;
    }

    /**
     * @phpstan-return Query<T>
     */
    public function first(string $fieldName): self
    {
        $this->aggregate = AggregateEnum::FIRST;
        $this->aggregateField = $fieldName;

        return $this;
    }

    /**
     * @phpstan-return Query<T>
     */
    public function last(string $fieldName): self
    {
        $this->aggregate = AggregateEnum::LAST;
        $this->aggregateField = $fieldName;

        return $this;
    }

    /**
     * @param string[] $fields
     *
     * @phpstan-return Query<T>
     */
    public function groupBy(array $fields): self
    {
        $this->groupBy = $fields;

        return $this;
    }

    /**
     * @phpstan-return Query<T>
     */
    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $this->orderBy = [['field' => $field, 'desc' => strtolower($direction) === 'desc']];

        return $this;
    }

    /**
     * @phpstan-return Query<T>
     */
    public function addOrderBy(string $field, string $direction = 'asc'): self
    {
        $this->orderBy[] = ['field' => $field, 'desc' => strtolower($direction) === 'desc'];

        return $this;
    }

    /**
     * @phpstan-return Query<T>
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @phpstan-return Query<T>
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @phpstan-return Query<T>
     */
    public function range(?DateTimeInterface $from, ?DateTimeInterface $to): self
    {
        if ($from) {
            $this->dateFrom = (int) $from->format('Uu000');
        }

        if ($to) {
            $this->dateTo = (int) $to->format('Uu000');
        }

        return $this;
    }

    /**
     * @phpstan-return Query<T>
     */
    public function addImport(string $import): self
    {
        if (!in_array($import, $this->imports, true)) {
            $this->imports[] = $import;
        }

        return $this;
    }

    /**
     * @phpstan-return Query<T>
     */
    public function fill(string $field, mixed $value): self
    {
        $this->fills[$field] = $value;

        return $this;
    }

    public function getFluxQuery(): string
    {
        $query = sprintf(
            <<<FLUX
                %s
                from(bucket:"%s")
                    |> range(start: time(v: %d), stop: time(v: %d))
                    |> filter(fn: (r) => r._measurement == "%s")
                    |> pivot(rowKey: ["_time"], columnKey: ["_field"], valueColumn: "_value")
                    |> group(columns: [])
            FLUX,
            $this->imports
                ? implode(
                    PHP_EOL,
                    array_map(static fn (string $import) => sprintf('import "%s"', $import), $this->imports),
                ) . PHP_EOL
                : '',
            $this->measurementManager->getClient()->getDatabase(),
            $this->dateFrom,
            $this->dateTo,
            $this->from,
        );

        if ($this->select) {
            $query .= sprintf(
                ' |> keep(columns: [%s])',
                implode(', ', array_map($this->quoteFieldName(...), $this->select)),
            );
        }

        if ($this->fills) {
            foreach ($this->fills as $field => $value) {
                $query .= sprintf(' |> fill(column: "%s", value: %s)', $field, $value);
            }
        }

        if ($this->where) {
            $query .= sprintf(' |> filter(fn: (r) => %s)', implode(' and ', $this->where));
        }

        if ($this->aggregate !== null) {
            $query .= sprintf(
                ' |> duplicate(column: %s, as: "_value")',
                $this->quoteFieldName((string) $this->aggregateField),
            );
        }

        if (!empty($this->groupBy)) {
            $query .= sprintf(
                ' |> group(columns: [%s])',
                implode(', ', array_map($this->quoteFieldName(...), $this->groupBy)),
            );
        }

        if ($this->orderBy) {
            foreach ($this->orderBy as $orderBy) {
                $query .= sprintf(
                    ' |> sort(columns: [%s], desc: %s)',
                    $this->quoteFieldName($orderBy['field']),
                    $orderBy['desc'] ? 'true' : 'false',
                );
            }
        }

        if ($this->limit) {
            $query .= sprintf(' |> limit(n: %d, offset: %d)', $this->limit, $this->offset);
        }

        if ($this->aggregatePeriod !== null) {
            $query .= sprintf(
                ' |> duplicate(column: %s, as: "_value") |> aggregateWindow(every: %s, fn: %s)',
                $this->quoteFieldName((string) $this->aggregateField),
                $this->aggregatePeriod,
                (string) $this->aggregate?->value,
            );
        } elseif ($this->aggregate !== null) {
            $query .= sprintf(' |> %s()', $this->aggregate->value);
        }

        if (!empty($this->groupBy)) {
            $query .= ' |> group(columns: [])';
        }

        return $query;
    }

    public function quoteFieldName(string $fieldName): string
    {
        $fieldName = $this->classMetadata->getFieldDatabaseName($fieldName);

        return sprintf('"%s"', addslashes($fieldName));
    }

    /**
     * @phpstan-return Query<T>
     */
    private function processIdWhere(DateTimeInterface $dateTime): self
    {
        $this->range($dateTime, $dateTime);

        $this->dateTo++;

        return $this;
    }
}
