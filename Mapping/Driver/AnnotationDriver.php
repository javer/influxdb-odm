<?php

namespace Javer\InfluxDB\ODM\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;
use Javer\InfluxDB\ODM\Mapping\Annotations\Field;
use Javer\InfluxDB\ODM\Mapping\Annotations\Measurement;
use Javer\InfluxDB\ODM\Mapping\ClassMetadata;
use Javer\InfluxDB\ODM\Mapping\MappingException;
use ReflectionClass;

class AnnotationDriver extends AbstractAnnotationDriver
{
    /**
     * {@inheritDoc}
     *
     * @phpstan-param class-string $className
     */
    public function isTransient($className): bool
    {
        $classAnnotations = $this->reader->getClassAnnotations(new ReflectionClass($className));

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
     * @phpstan-param class-string $className
     */
    public function loadMetadataForClass($className, BaseClassMetadata $metadata): void
    {
        assert($metadata instanceof ClassMetadata);
        $reflClass = $metadata->getReflectionClass();
        $classAnnotations = $this->reader->getClassAnnotations($reflClass);
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

            foreach ($this->reader->getPropertyAnnotations($property) as $annotation) {
                if ($annotation instanceof Field) {
                    $mapping = array_replace($mapping, (array) $annotation);

                    // @phpstan-ignore-next-line: Array structure will be fixed inside mapField()
                    $metadata->mapField($mapping);
                }
            }
        }
    }

    /**
     * Factory method for the Annotation Driver
     *
     * @param string[]|string $paths
     * @param Reader|null     $reader
     *
     * @return AnnotationDriver
     */
    public static function create($paths = [], ?Reader $reader = null): AnnotationDriver
    {
        if ($reader === null) {
            $reader = new AnnotationReader();
        }

        return new self($reader, $paths);
    }
}
