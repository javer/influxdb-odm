<?php

namespace Javer\InfluxDB\ODM\Hydrator;

use Javer\InfluxDB\ODM\Mapping\ClassMetadata;

/**
 * @template T of object
 */
abstract class AbstractHydrator implements HydratorInterface
{
    /**
     * @phpstan-var ClassMetadata<T>
     */
    protected ClassMetadata $classMetadata;

    /**
     * AbstractHydrator constructor.
     *
     * @param ClassMetadata $classMetadata
     *
     * @phpstan-param ClassMetadata<T> $classMetadata
     */
    public function __construct(ClassMetadata $classMetadata)
    {
        $this->classMetadata = $classMetadata;
    }
}
