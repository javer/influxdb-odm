<?php

namespace Javer\InfluxDB\ODM\Hydrator;

interface HydratorInterface
{
    /**
     * Hydrate data from the database.
     *
     * @param array<string, mixed> $data
     *
     * @return mixed
     */
    public function hydrate(array $data): mixed;
}
