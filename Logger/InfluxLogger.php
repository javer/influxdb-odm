<?php

namespace Javer\InfluxDB\ODM\Logger;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @phpstan-type T array{query: string, time: float, rows: int, error?: string|null}
 */
final class InfluxLogger implements InfluxLoggerInterface
{
    /**
     * @var mixed[]
     *
     * @phpstan-var array<int, T>
     */
    private array $queries = [];

    /**
     * @var mixed[]
     *
     * @phpstan-var array<int, T>
     */
    private array $writes = [];

    /**
     * @var mixed[]
     *
     * @phpstan-var array<int, T>
     */
    private array $deletions = [];

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    )
    {
    }

    public function logQuery(string $query, float $time, int $rows = 1, ?Throwable $exception = null): void
    {
        $this->queries[] = [
            'query' => $query,
            'rows' => $rows,
            'time' => $time,
            'error' => $exception?->getMessage(),
        ];

        $this->logger?->debug($query);
    }

    public function logWrite(string $query, float $time, int $rows = 1, ?Throwable $exception = null): void
    {
        $this->writes[] = [
            'query' => $query,
            'rows' => $rows,
            'time' => $time,
            'error' => $exception?->getMessage(),
        ];

        $this->logger?->debug($query);
    }

    public function logDelete(string $query, float $time, int $rows = 1, ?Throwable $exception = null): void
    {
        $this->deletions[] = [
            'query' => $query,
            'rows' => $rows,
            'time' => $time,
            'error' => $exception?->getMessage(),
        ];

        $this->logger?->debug($query);
    }

    /**
     * {@inheritDoc}
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * {@inheritDoc}
     */
    public function getWrites(): array
    {
        return $this->writes;
    }

    /**
     * {@inheritDoc}
     */
    public function getDeletions(): array
    {
        return $this->deletions;
    }

    public function getQueriesCount(): int
    {
        return count($this->queries) + count($this->writes) + count($this->deletions);
    }

    public function getQueriesRows(): int
    {
        return array_sum(array_column($this->queries, 'rows'))
            + array_sum(array_column($this->writes, 'rows'))
            + array_sum(array_column($this->deletions, 'rows'));
    }

    public function getQueriesTime(): float
    {
        return array_sum(array_column($this->queries, 'time'))
            + array_sum(array_column($this->writes, 'time'))
            + array_sum(array_column($this->deletions, 'time'));
    }

    public function getErrorsCount(): int
    {
        return count(array_filter(array_column($this->queries, 'error')))
            + count(array_filter(array_column($this->writes, 'error')))
            + count(array_filter(array_column($this->deletions, 'error')));
    }

    public function reset(): void
    {
        $this->queries = [];
        $this->writes = [];
        $this->deletions = [];
    }
}
