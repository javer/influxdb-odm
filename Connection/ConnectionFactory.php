<?php

namespace Javer\InfluxDB\ODM\Connection;

use InfluxDB\Client;
use InfluxDB\Database;

class ConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * @var array<string, Database>
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

        assert($database instanceof Database);

        $this->databases[$dsn] = $database;

        return $database;
    }
}
