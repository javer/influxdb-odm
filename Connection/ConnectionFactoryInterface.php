<?php

namespace Javer\InfluxDB\ODM\Connection;

use InfluxDB\Client\Exception as ClientException;
use InfluxDB\Database;

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
