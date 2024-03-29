<?php

namespace Javer\InfluxDB\ODM\Mapping;

use Doctrine\Persistence\Mapping\MappingException as BaseMappingException;
use Javer\InfluxDB\ODM\Mapping\Annotations\Measurement;
use ReflectionException;
use ReflectionObject;

class MappingException extends BaseMappingException
{
    /**
     * Type exists.
     *
     * @param string $name
     *
     * @return self
     */
    public static function typeExists(string $name): self
    {
        return new self(sprintf('Type %s already exists.', $name));
    }

    /**
     * Type not found.
     *
     * @param string $name
     *
     * @return self
     */
    public static function typeNotFound(string $name): self
    {
        return new self(sprintf('Type to be overwritten %s does not exist.', $name));
    }

    /**
     * Mapping not found.
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return self
     *
     * @phpstan-param class-string $className
     */
    public static function mappingNotFound(string $className, string $fieldName): self
    {
        return new self(sprintf("No mapping found for field '%s' in class '%s'.", $fieldName, $className));
    }

    /**
     * Missing field name.
     *
     * @param string $className
     *
     * @return self
     *
     * @phpstan-param class-string $className
     */
    public static function missingFieldName(string $className): self
    {
        return new self(
            sprintf("The Measurement class '%s' field mapping misses the 'fieldName' attribute.", $className)
        );
    }

    /**
     * Tag or Id cannot be countable.
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return self
     *
     * @phpstan-param class-string $className
     */
    public static function tagOrIdCannotBeCountable(string $className, string $fieldName): self
    {
        return new self(
            sprintf("Tag or Id '%s' cannot be countable in the Measurement class '%s'.", $fieldName, $className)
        );
    }

    /**
     * Has several countable fields.
     *
     * @param string $className
     *
     * @return self
     *
     * @phpstan-param class-string $className
     */
    public static function hasSeveralCountableFields(string $className): self
    {
        return new self(
            sprintf("The Measurement class '%s' field mapping has several countable fields.", $className)
        );
    }

    /**
     * Missing countable field.
     *
     * @param string $className
     *
     * @return self
     *
     * @phpstan-param class-string $className
     */
    public static function missingCountableField(string $className): self
    {
        return new self(
            sprintf("The Measurement class '%s' field mapping misses the countable field.", $className)
        );
    }

    /**
     * Class is not a valid measurement.
     *
     * @param string $className
     *
     * @return self
     *
     * @phpstan-param class-string $className
     */
    public static function classIsNotAValidMeasurement(string $className): self
    {
        return new self(sprintf('Class %s is not a valid measurement.', $className));
    }

    /**
     * Class can only be mapped by one measurement.
     *
     * @param string      $className
     * @param Measurement $mappedAs
     * @param Measurement $offending
     *
     * @return self
     *
     * @phpstan-param class-string $className
     */
    public static function classCanOnlyBeMappedByOneMeasurement(
        string $className,
        Measurement $mappedAs,
        Measurement $offending
    ): self
    {
        return new self(sprintf(
            "Can not map class '%s' as %s because it was already mapped as %s.",
            $className,
            (new ReflectionObject($offending))->getShortName(),
            (new ReflectionObject($mappedAs))->getShortName()
        ));
    }

    /**
     * Reflection failure.
     *
     * @param string              $measurement
     * @param ReflectionException $previousException
     *
     * @return self
     */
    public static function reflectionFailure(string $measurement, ReflectionException $previousException): self
    {
        return new self('An error occurred in ' . $measurement, 0, $previousException);
    }

    /**
     * Missing identifier field.
     *
     * @param string $className
     *
     * @return self
     *
     * @phpstan-param class-string $className
     */
    public static function missingIdentifierField(string $className): self
    {
        return new self(sprintf('The identifier is missing for a query of %s', $className));
    }
}
