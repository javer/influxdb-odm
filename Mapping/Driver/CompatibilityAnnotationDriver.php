<?php

namespace Javer\InfluxDB\ODM\Mapping\Driver;

use Doctrine\Persistence\Mapping\Driver\AnnotationDriver as PersistenceAnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

use function class_exists;

if (class_exists(PersistenceAnnotationDriver::class)) {
    /**
     * @internal This class will be removed in ODM 2.0.
     */
    abstract class CompatibilityAnnotationDriver extends PersistenceAnnotationDriver
    {
    }
} else {
    /**
     * @internal This class will be removed in ODM 2.0.
     */
    abstract class CompatibilityAnnotationDriver implements MappingDriver
    {
    }
}
