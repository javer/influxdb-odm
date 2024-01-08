<?php

namespace Javer\InfluxDB\ODM\Mapping\Driver;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\Persistence\Mapping\Driver\ColocatedMappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Javer\InfluxDB\ODM\Mapping\Annotations\Field;
use Javer\InfluxDB\ODM\Mapping\Annotations\Measurement;
use Javer\InfluxDB\ODM\Mapping\ClassMetadata;
use Javer\InfluxDB\ODM\Mapping\MappingException;
use ReflectionClass;
use ReflectionProperty;

class AttributeDriver implements MappingDriver
{
    use ColocatedMappingDriver;

    /**
     * @var Reader|AttributeReader
     */
    protected $reader;

    /**
     * @param string|string[]|null $paths
     * @param Reader|null          $reader
     */
    public function __construct($paths = null, ?Reader $reader = null)
    {
        $this->reader = $reader ?? new AttributeReader();

        $this->addPaths((array) $paths);
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-param class-string $className
     */
    public function isTransient($className): bool
    {
        $classAnnotations = $this->getClassAttributes(new ReflectionClass($className));

        foreach ($classAnnotations as $annotation) {
            if ($annotation instanceof Measurement) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @throws MappingException
     *
     * @phpstan-param class-string         $className
     * @phpstan-param BaseClassMetadata<T> $metadata
     *
     * @template T of object
     */
    public function loadMetadataForClass($className, BaseClassMetadata $metadata): void
    {
        assert($metadata instanceof ClassMetadata);
        $reflClass = $metadata->getReflectionClass();
        $classAnnotations = $this->getClassAttributes($reflClass);
        $measurementAnnotation = null;

        foreach ($classAnnotations as $annotation) {
            if ($annotation instanceof Measurement) {
                if ($measurementAnnotation !== null) {
                    throw MappingException::classCanOnlyBeMappedByOneMeasurement(
                        $className,
                        $measurementAnnotation,
                        $annotation
                    );
                }

                $measurementAnnotation = $annotation;
            }
        }

        if ($measurementAnnotation === null) {
            throw MappingException::classIsNotAValidMeasurement($className);
        }

        if (isset($measurementAnnotation->name)) {
            $metadata->setMeasurement($measurementAnnotation->name);
        }

        if (isset($measurementAnnotation->repositoryClass)) {
            $metadata->setCustomRepositoryClassName($measurementAnnotation->repositoryClass);
        }

        foreach ($reflClass->getProperties() as $property) {
            $mapping = ['fieldName' => $property->getName()];

            foreach ($this->getPropertyAttributes($property) as $annotation) {
                if ($annotation instanceof Field) {
                    $mapping = array_replace($mapping, (array) $annotation);

                    // @phpstan-ignore-next-line: Array structure will be fixed inside mapField()
                    $metadata->mapField($mapping);
                }
            }
        }
    }

    /**
     * @param string[]|string $paths
     * @param Reader|null     $reader
     *
     * @return AttributeDriver
     */
    public static function create($paths = [], ?Reader $reader = null): AttributeDriver
    {
        return new self($paths, $reader);
    }

    /**
     * @return object[]
     *
     * @phpstan-param ReflectionClass<object> $class
     */
    private function getClassAttributes(ReflectionClass $class): array
    {
        if ($this->reader instanceof AttributeReader) {
            return $this->reader->getClassAttributes($class);
        }

        return $this->reader->getClassAnnotations($class);
    }

    /**
     * @return object[]
     */
    private function getPropertyAttributes(ReflectionProperty $property): array
    {
        if ($this->reader instanceof AttributeReader) {
            return $this->reader->getPropertyAttributes($property);
        }

        return $this->reader->getPropertyAnnotations($property);
    }
}
