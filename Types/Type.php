<?php

namespace Javer\InfluxDB\ODM\Types;

use DateTimeInterface;
use InvalidArgumentException;
use Javer\InfluxDB\ODM\Mapping\MappingException;

abstract class Type
{
    public const TIMESTAMP = 'timestamp';
    public const BOOLEAN = 'boolean';
    public const INTEGER = 'integer';
    public const FLOAT = 'float';
    public const STRING = 'string';

    /**
     * Map of already instantiated type objects. One instance per type.
     *
     * @var Type[]
     */
    private static array $typeObjects = [];

    /**
     * The map of supported doctrine mapping types.
     *
     * @var array<string, class-string>
     */
    private static array $typesMap = [
        self::TIMESTAMP => TimestampType::class,
        self::BOOLEAN => BooleanType::class,
        self::INTEGER => IntegerType::class,
        self::FLOAT => FloatType::class,
        self::STRING => StringType::class,
    ];

    /**
     * Type constructor.
     */
    final private function __construct()
    {
    }

    /**
     * Converts a value from its PHP representation to its database representation of this type.
     *
     * @param mixed $value The value to convert.
     *
     * @return mixed The database representation of the value.
     */
    public function convertToDatabaseValue($value)
    {
        return $value;
    }

    /**
     * Converts a value from its database representation to its PHP representation of this type.
     *
     * @param mixed $value The value to convert.
     *
     * @return mixed The PHP representation of the value.
     */
    public function convertToPHPValue($value)
    {
        return $value;
    }

    /**
     * Register a new type in the type map.
     *
     * @param string $name
     * @param string $class
     *
     * @phpstan-param class-string $class
     */
    public static function registerType(string $name, string $class): void
    {
        self::$typesMap[$name] = $class;
    }

    /**
     * Get a Type instance.
     *
     * @param string $type
     *
     * @return Type
     *
     * @throws InvalidArgumentException
     */
    public static function getType(string $type): Type
    {
        if (!isset(self::$typesMap[$type])) {
            throw new InvalidArgumentException(sprintf('Invalid type specified "%s".', $type));
        }

        if (!isset(self::$typeObjects[$type])) {
            $className = self::$typesMap[$type];

            self::$typeObjects[$type] = new $className();
        }

        return self::$typeObjects[$type];
    }

    /**
     * Get a Type instance based on the type of the passed php variable.
     *
     * @param mixed $variable
     *
     * @return Type|null
     *
     * @throws InvalidArgumentException
     */
    public static function getTypeFromPHPVariable($variable): ?Type
    {
        if (is_object($variable)) {
            if ($variable instanceof DateTimeInterface) {
                return self::getType(self::TIMESTAMP);
            }
        } else {
            $type = gettype($variable);

            switch ($type) {
                case 'boolean':
                    return self::getType(self::BOOLEAN);
                case 'integer':
                    return self::getType(self::INTEGER);
                case 'float':
                    return self::getType(self::FLOAT);
                case 'string':
                    return self::getType(self::STRING);
            }
        }

        return null;
    }

    /**
     * Convert PHP to database value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function convertPHPToDatabaseValue($value)
    {
        $type = self::getTypeFromPHPVariable($value);

        if ($type !== null) {
            return $type->convertToDatabaseValue($value);
        }

        return $value;
    }

    /**
     * Adds a custom type to the type map.
     *
     * @param string $name
     * @param string $className
     *
     * @throws MappingException
     *
     * @phpstan-param class-string $className
     */
    public static function addType(string $name, string $className): void
    {
        if (isset(self::$typesMap[$name])) {
            throw MappingException::typeExists($name);
        }

        self::$typesMap[$name] = $className;
    }

    /**
     * Checks if exists support for a type.
     *
     * @param string $name
     *
     * @return boolean
     */
    public static function hasType(string $name): bool
    {
        return isset(self::$typesMap[$name]);
    }

    /**
     * Overrides an already defined type to use a different implementation.
     *
     * @param string $name
     * @param string $className
     *
     * @throws MappingException
     *
     * @phpstan-param class-string $className
     */
    public static function overrideType(string $name, string $className): void
    {
        if (!isset(self::$typesMap[$name])) {
            throw MappingException::typeNotFound($name);
        }

        self::$typesMap[$name] = $className;
    }

    /**
     * Get the types array map which holds all registered types and the corresponding type class.
     *
     * @return array<string, class-string>
     */
    public static function getTypesMap(): array
    {
        return self::$typesMap;
    }

    /**
     * Returns string representation of the object.
     *
     * @return string
     */
    public function __toString(): string
    {
        $type = static::class;
        $position = strrpos($type, '\\');

        if ($position !== false) {
            $type = substr($type, $position);
        }

        return str_replace('Type', '', $type);
    }
}
