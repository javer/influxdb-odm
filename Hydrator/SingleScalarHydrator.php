<?php

namespace Javer\InfluxDB\ODM\Hydrator;

/**
 * @template T of object
 * @template-extends  AbstractHydrator<T>
 */
class SingleScalarHydrator extends AbstractHydrator
{
    /**
     * Hydrate data from the database.
     *
     * @param array<string, mixed> $data
     *
     * @return mixed
     */
    public function hydrate(array $data)
    {
        $result = array_values($data);

        return count($result) > 1 ? $result[1] : null;
    }
}
