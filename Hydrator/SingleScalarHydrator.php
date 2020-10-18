<?php

namespace Javer\InfluxDB\ODM\Hydrator;

/**
 * Class SingleScalarHydrator
 *
 * @package Javer\InfluxDB\ODM\Hydrator
 */
class SingleScalarHydrator extends AbstractHydrator
{
    /**
     * {@inheritDoc}
     */
    public function hydrate(array $data)
    {
        $result = array_values($data);

        return count($result) > 1 ? $result[1] : null;
    }
}
