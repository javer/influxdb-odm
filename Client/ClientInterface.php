<?php

namespace Javer\InfluxDB\ODM\Client;

use InfluxDB2\FluxTable;
use InfluxDB2\Point;
use Javer\InfluxDB\ODM\Model\DeletionPoint;
use Throwable;

interface ClientInterface
{
    /**
     * @param string $query FLUX query
     *
     * @throws Throwable
     */
    public function query(string $query): ?FluxTable;

    /**
     * @param Point[] $data
     *
     * @throws Throwable
     */
    public function write(array $data): void;

    /**
     * @param DeletionPoint[] $data
     *
     * @throws Throwable
     */
    public function delete(array $data): void;

    public function dropMeasurement(string $measurement): void;

    public function getDatabase(): string;
}
