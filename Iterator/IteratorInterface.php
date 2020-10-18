<?php

namespace Javer\InfluxDB\ODM\Iterator;

use Iterator;

/**
 * Interface Iterator
 *
 * @package Javer\InfluxDB\ODM\Iterator
 */
interface IteratorInterface extends Iterator
{
    /**
     * Returns array representation.
     *
     * @return array
     */
    public function toArray(): array;
}
