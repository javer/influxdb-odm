<?php

namespace Javer\InfluxDB\ODM\Mapping\Driver;

use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\Persistence\Mapping\Driver\ColocatedMappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Javer\InfluxDB\ODM\Mapping\Attributes\Field;
use Javer\InfluxDB\ODM\Mapping\Attributes\Measurement;
use Javer\InfluxDB\ODM\Mapping\Attributes\Tag;
use Javer\InfluxDB\ODM\Mapping\ClassMetadata;
use Javer\InfluxDB\ODM\Mapping\MappingException;
use Javer\InfluxDB\ODM\Types\TypeEnum;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

final class AttributeDriver implements MappingDriver
{
    use ColocatedMappingDriver;

    /**
     * Initializes a new AttributeDriver that uses the given AttributeReader for reading class attributes.
     *
     * @param AttributeReader $reader The AttributeReader to use.
     * @param string[]        $paths  One or multiple paths where mapping classes can be found.
     */
    public function __construct(
        private readonly AttributeReader $reader,
        array $paths = [],
    )
    {
        $this->addPaths($paths);
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-param class-string $className
     */
    public function isTransient(string $className): bool
    {
        $classAttributes = $this->reader->getClassAttributes(new ReflectionClass($className));

        foreach ($classAttributes as $attribute) {
            if ($attribute instanceof Measurement) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws MappingException
     *
     * @phpstan-param class-string<T>      $className
     * @phpstan-param BaseClassMetadata<T> $metadata
     *
     * @template T of object
     */
    public function loadMetadataForClass(string $className, BaseClassMetadata $metadata): void
    {
        assert($metadata instanceof ClassMetadata);
        $reflClass = $metadata->getReflectionClass();
        $classAttributes = $this->reader->getClassAttributes($reflClass);
        $measurementAttribute = null;

        foreach ($classAttributes as $attribute) {
            if ($attribute instanceof Measurement) {
                if ($measurementAttribute !== null) {
                    throw MappingException::classCanOnlyBeMappedByOneMeasurement(
                        $className,
                        $measurementAttribute,
                        $attribute,
                    );
                }

                $measurementAttribute = $attribute;
            }
        }

        if ($measurementAttribute === null) {
            throw MappingException::classIsNotAValidMeasurement($className);
        }

        if ($measurementAttribute->name !== null) {
            $metadata->setMeasurement($measurementAttribute->name);
        }

        if ($measurementAttribute->repositoryClass !== null) {
            $metadata->setCustomRepositoryClassName($measurementAttribute->repositoryClass);
        }

        foreach ($reflClass->getProperties() as $property) {
            $fieldName = $property->getName();

            foreach ($this->reader->getPropertyAttributes($property) as $attribute) {
                if ($attribute instanceof Field) {
                    $mapping = [
                        'fieldName' => $fieldName,
                        'name' => $attribute->name ?? $fieldName,
                        'type' => $attribute->type ?? $this->guessType($property),
                        'tag' => $attribute instanceof Tag,
                        'countable' => $attribute->countable,
                    ];

                    $metadata->mapField($mapping);

                    break;
                }
            }
        }
    }

    private function guessType(ReflectionProperty $reflectionProperty): ?TypeEnum
    {
        $reflectionType = $reflectionProperty->getType();

        if ($reflectionType instanceof ReflectionNamedType) {
            return match ($reflectionType->getName()) {
                'string' => TypeEnum::STRING,
                'float' => TypeEnum::FLOAT,
                'bool' => TypeEnum::BOOLEAN,
                'int' => TypeEnum::INTEGER,
                default => null,
            };
        }

        return null;
    }
}
