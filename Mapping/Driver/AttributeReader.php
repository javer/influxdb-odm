<?php

namespace Javer\InfluxDB\ODM\Mapping\Driver;

use Doctrine\Common\Annotations\Reader;
use Javer\InfluxDB\ODM\Mapping\Annotations\Annotation;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class AttributeReader implements Reader
{
    /**
     * @return Annotation[]
     *
     * @phpstan-param ReflectionClass<object> $class
     */
    public function getClassAnnotations(ReflectionClass $class): array
    {
        return $this->convertToAttributeInstances($class->getAttributes());
    }

    /**
     * {@inheritDoc}
     *
     * @return Annotation|null
     *
     * @phpstan-param ReflectionClass<object>  $class
     * @phpstan-param class-string<Annotation> $annotationName
     */
    public function getClassAnnotation(ReflectionClass $class, $annotationName)
    {
        foreach ($this->getClassAnnotations($class) as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return Annotation[]
     */
    public function getMethodAnnotations(ReflectionMethod $method): array
    {
        return $this->convertToAttributeInstances($method->getAttributes());
    }

    /**
     * @param ReflectionMethod $method
     * @param string           $annotationName
     *
     * @return Annotation|null
     */
    public function getMethodAnnotation(ReflectionMethod $method, $annotationName)
    {
        foreach ($this->getMethodAnnotations($method) as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @return Annotation[]
     */
    public function getPropertyAnnotations(ReflectionProperty $property): array
    {
        return $this->convertToAttributeInstances($property->getAttributes());
    }

    /**
     * {@inheritDoc}
     *
     * @return Annotation|null
     *
     * @phpstan-param class-string<Annotation> $annotationName
     */
    public function getPropertyAnnotation(ReflectionProperty $property, $annotationName)
    {
        foreach ($this->getPropertyAnnotations($property) as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * @param ReflectionAttribute<object>[] $attributes
     *
     * @return Annotation[]
     */
    private function convertToAttributeInstances(array $attributes): array
    {
        $instances = [];

        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            assert(is_string($attributeName));

            // Make sure we only get Doctrine Annotations
            if (!is_subclass_of($attributeName, Annotation::class)) {
                continue;
            }

            $instance = $attribute->newInstance();
            assert($instance instanceof Annotation);
            $instances[] = $instance;
        }

        return $instances;
    }
}
