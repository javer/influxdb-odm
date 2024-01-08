<?php

namespace Javer\InfluxDB\ODM\Mapping;

use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\Persistence\Mapping\ReflectionService;
use Javer\InfluxDB\ODM\Mapping\Driver\AnnotationDriver;
use ReflectionException;

/**
 * @template T of object
 * @template-extends AbstractClassMetadataFactory<ClassMetadata<T>>
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
     *
     * @phpstan-param  class-string<T> $className
     * @phpstan-return ClassMetadata<T>
     */
    public function getMetadataFor(string $className): ClassMetadata
    {
        $metadata = parent::getMetadataFor($className);

        assert($metadata instanceof ClassMetadata);

        return $metadata;
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
     * @param BaseClassMetadata      $class
     * @param BaseClassMetadata|null $parent
     * @param bool                   $rootEntityFound
     * @param string[]               $nonSuperclassParents
     *
     * @throws MappingException
     *
     * @phpstan-param BaseClassMetadata<T>      $class
     * @phpstan-param BaseClassMetadata<T>|null $parent
     */
    protected function doLoadMetadata(
        BaseClassMetadata $class,
        ?BaseClassMetadata $parent,
        bool $rootEntityFound,
        array $nonSuperclassParents,
    ): void
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
     *
     * @phpstan-param  class-string<T> $className
     * @phpstan-return ClassMetadata<T>
     */
    protected function newClassMetadataInstance(string $className): ClassMetadata
    {
        return new ClassMetadata($className);
    }
}
