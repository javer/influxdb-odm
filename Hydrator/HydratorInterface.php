<?php

namespace Javer\InfluxDB\ODM\Hydrator;

/**
 * Interface HydratorInterface
 *
 * @package Javer\InfluxDB\ODM\Hydrator
 */
interface HydratorInterface
{
    /**
     * Hydrate data from the database.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function hydrate(array $data);
}
