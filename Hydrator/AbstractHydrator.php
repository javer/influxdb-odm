<?php

namespace Javer\InfluxDB\ODM\Hydrator;

use Javer\InfluxDB\ODM\Mapping\ClassMetadata;

/**
 * @template T of object
 */
abstract class AbstractHydrator implements HydratorInterface
{
    /**
     * Constructor.
     *
     * @phpstan-param ClassMetadata<T> $classMetadata
     */
    public function __construct(
        protected readonly ClassMetadata $classMetadata,
    )
    {
    }
}
