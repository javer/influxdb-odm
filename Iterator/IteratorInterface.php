<?php

namespace Javer\InfluxDB\ODM\Iterator;

use Iterator;

/**
 * @template-extends Iterator<mixed>
 */
interface IteratorInterface extends Iterator
{
    /**
     * Returns array representation.
     *
     * @return array<mixed>
     */
    public function toArray(): array;
}
