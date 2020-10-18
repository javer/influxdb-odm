<?php

namespace Javer\InfluxDB\ODM\Connection;

use InfluxDB\Client\Exception as ClientException;
use InfluxDB\Database;

/**
 * Interface ConnectionFactoryInterface
 *
 * @package Javer\InfluxDB\ODM\Connection
 */
interface ConnectionFactoryInterface
{
    /**
     * Creates a database connection.
     *
     * @param string $dsn
     *
     * @return Database
     *
     * @throws ClientException
     */
    public function createConnection(string $dsn): Database;
}
