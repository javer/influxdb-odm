<?php

namespace Javer\InfluxDB\ODM\Mapping;

use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\Persistence\Mapping\ReflectionService;
use Javer\InfluxDB\ODM\Mapping\Driver\AttributeDriver;
use ReflectionException;

/**
 * @template T of object
 *
 * @template-extends AbstractClassMetadataFactory<ClassMetadata<T>>
 */
final class ClassMetadataFactory extends AbstractClassMetadataFactory
{
    public function __construct(
        private readonly AttributeDriver $driver,
    )
    {
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-param class-string<T> $className
     *
     * @phpstan-return ClassMetadata<T>
     */
    public function getMetadataFor(string $className): ClassMetadata
    {
        $metadata = parent::getMetadataFor($className);

        assert($metadata instanceof ClassMetadata);

        return $metadata;
    }

    protected function initialize(): void
    {
        $this->initialized = true;
    }

    protected function getDriver(): AttributeDriver
    {
        return $this->driver;
    }

    protected function wakeupReflection(BaseClassMetadata $class, ReflectionService $reflService): void
    {
    }

    protected function initializeReflection(BaseClassMetadata $class, ReflectionService $reflService): void
    {
    }

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
     * @phpstan-param BaseClassMetadata<T>      $class
     * @phpstan-param BaseClassMetadata<T>|null $parent
     *
     * @throws MappingException
     */
    protected function doLoadMetadata(
        BaseClassMetadata $class,
        ?BaseClassMetadata $parent,
        bool $rootEntityFound,
        array $nonSuperclassParents,
    ): void
    {
        assert($class instanceof ClassMetadata);

        try {
            $this->driver->loadMetadataForClass($class->getName(), $class);
        } catch (ReflectionException $e) {
            throw MappingException::reflectionFailure($class->getName(), $e);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-param class-string<T> $className
     *
     * @phpstan-return ClassMetadata<T>
     */
    protected function newClassMetadataInstance(string $className): ClassMetadata
    {
        return new ClassMetadata($className);
    }
}
