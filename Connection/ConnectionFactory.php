<?php

namespace Javer\InfluxDB\ODM\Connection;

use InfluxDB\Client;
use InfluxDB\Database;

/**
 * Class ConnectionFactory
 *
 * @package Javer\InfluxDB\ODM\Connection
 */
class ConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * @var Database[]
     */
    private array $databases = [];

    /**
     * Create database connection.
     *
     * @param string $dsn
     *
     * @return Database
     */
    public function createConnection(string $dsn): Database
    {
        if (isset($this->databases[$dsn])) {
            return $this->databases[$dsn];
        }

        $database = Client::fromDSN($dsn);

        $this->databases[$dsn] = $database;

        return $database;
    }
}
