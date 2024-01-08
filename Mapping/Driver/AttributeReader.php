<?php

namespace Javer\InfluxDB\ODM\Mapping\Driver;

use Javer\InfluxDB\ODM\Mapping\Annotations\Annotation;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;

final class AttributeReader
{
    /**
     * @return Annotation[]
     *
     * @phpstan-param ReflectionClass<object> $class
     */
    public function getClassAttributes(ReflectionClass $class): array
    {
        return $this->convertToAttributeInstances($class->getAttributes());
    }

    /**
     * @return Annotation[]
     */
    public function getPropertyAttributes(ReflectionProperty $property): array
    {
        return $this->convertToAttributeInstances($property->getAttributes());
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
