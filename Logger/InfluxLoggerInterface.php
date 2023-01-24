<?php

namespace Javer\InfluxDB\ODM\Logger;

use Throwable;

/**
 * @phpstan-type T array{query: string, time: float, rows: int, error?: string|null}
 */
interface InfluxLoggerInterface
{
    public function logQuery(string $query, float $time, int $rows = 1, ?Throwable $exception = null): void;

    public function logWrite(string $query, float $time, int $rows = 1, ?Throwable $exception = null): void;

    public function logDelete(string $query, float $time, int $rows = 1, ?Throwable $exception = null): void;

    /**
     * @return mixed[]
     *
     * @phpstan-return array<int, T>
     */
    public function getQueries(): array;

    /**
     * @return mixed[]
     *
     * @phpstan-return array<int, T>
     */
    public function getWrites(): array;

    /**
     * @return mixed[]
     *
     * @phpstan-return array<int, T>
     */
    public function getDeletions(): array;

    public function getQueriesCount(): int;

    public function getQueriesRows(): int;

    public function getQueriesTime(): float;

    public function getErrorsCount(): int;
}
