<?php

namespace Javer\InfluxDB\ODM\Mapping;

use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\Persistence\Mapping\ReflectionService;
use Javer\InfluxDB\ODM\Mapping\Driver\AnnotationDriver;
use ReflectionException;

/**
 * Class ClassMetadataFactory
 *
 * @package Javer\InfluxDB\ODM\Mapping
 */
class ClassMetadataFactory extends AbstractClassMetadataFactory
{
    private AnnotationDriver $driver;

    /**
     * ClassMetadataFactory constructor.
     *
     * @param AnnotationDriver $annotationDriver
     */
    public function __construct(AnnotationDriver $annotationDriver)
    {
        $this->driver = $annotationDriver;
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(): void
    {
        $this->initialized = true;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName): string
    {
        return $namespaceAlias . '\\' . $simpleClassName;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDriver(): AnnotationDriver
    {
        return $this->driver;
    }

    /**
     * {@inheritDoc}
     */
    protected function wakeupReflection(BaseClassMetadata $class, ReflectionService $reflService): void
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeReflection(BaseClassMetadata $class, ReflectionService $reflService): void
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function isEntity(BaseClassMetadata $class): bool
    {
        return $class instanceof ClassMetadata;
    }

    /**
     * {@inheritDoc}
     *
     * @throws MappingException
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents): void
    {
        assert($class instanceof ClassMetadata);

        if ($parent instanceof ClassMetadata) {
            $class->setIdentifier($parent->identifier);
        }

        try {
            $this->driver->loadMetadataForClass($class->getName(), $class);
        } catch (ReflectionException $e) {
            throw MappingException::reflectionFailure($class->getName(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function newClassMetadataInstance($className): ClassMetadata
    {
        return new ClassMetadata($className);
    }
}
