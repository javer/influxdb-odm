<?php

namespace Javer\InfluxDB\ODM\Mapping\Driver;

use Javer\InfluxDB\ODM\Mapping\Attributes\MappingAttributeInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class AttributeReader
{
    /**
     * @return MappingAttributeInterface[]
     *
     * @phpstan-param ReflectionClass<object> $class
     */
    public function getClassAttributes(ReflectionClass $class): array
    {
        return $this->convertToAttributeInstances($class->getAttributes());
    }

    /**
     * @return MappingAttributeInterface[]
     */
    public function getMethodAttributes(ReflectionMethod $method): array
    {
        return $this->convertToAttributeInstances($method->getAttributes());
    }

    /**
     * @return MappingAttributeInterface[]
     */
    public function getPropertyAttributes(ReflectionProperty $property): array
    {
        return $this->convertToAttributeInstances($property->getAttributes());
    }

    /**
     * @param ReflectionAttribute<object>[] $attributes
     *
     * @return MappingAttributeInterface[]
     */
    private function convertToAttributeInstances(array $attributes): array
    {
        $instances = [];

        foreach ($attributes as $attribute) {
            if (!is_subclass_of($attribute->getName(), MappingAttributeInterface::class)) {
                continue;
            }

            $instance = $attribute->newInstance();
            assert($instance instanceof MappingAttributeInterface);
            $instances[] = $instance;
        }

        return $instances;
    }
}
