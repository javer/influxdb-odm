<?php

namespace Javer\InfluxDB\ODM\Client;

use InfluxDB2\Model\WritePrecision;
use Javer\InfluxDB\ODM\InfluxDBException;
use Javer\InfluxDB\ODM\Logger\InfluxLoggerInterface;

interface ClientFactoryInterface
{
    /**
     * @throws InfluxDBException
     */
    public function createClient(
        string $dsn,
        string $precision = WritePrecision::NS,
        ?InfluxLoggerInterface $logger = null,
    ): Client;
}
