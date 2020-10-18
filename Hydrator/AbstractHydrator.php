<?php

namespace Javer\InfluxDB\ODM\Hydrator;

use Javer\InfluxDB\ODM\Mapping\ClassMetadata;

/**
 * Class AbstractHydrator
 *
 * @package Javer\InfluxDB\ODM\Hydrator
 */
abstract class AbstractHydrator implements HydratorInterface
{
    protected ClassMetadata $classMetadata;

    /**
     * AbstractHydrator constructor.
     *
     * @param ClassMetadata $classMetadata
     */
    public function __construct(ClassMetadata $classMetadata)
    {
        $this->classMetadata = $classMetadata;
    }
}
