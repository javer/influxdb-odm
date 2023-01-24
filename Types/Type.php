<?php

namespace Javer\InfluxDB\ODM\Types;

use DateTimeInterface;
use ValueError;

abstract class Type
{
    /**
     * Map of already instantiated type objects. One instance per type.
     *
     * @var Type[]
     */
    private static array $typeObjects = [];

    /**
     * The map of supported mapping types.
     *
     * @var array<string, class-string<Type>>
     */
    private static array $typesMap = [];

    final private function __construct()
    {
    }

    /**
     * Converts a value from its PHP representation to its database representation of this type.
     */
    public function convertToDatabaseValue(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Converts a value from its database representation to its PHP representation of this type.
     */
    public function convertToPHPValue(mixed $value): mixed
    {
        return $value;
    }

    public static function getType(TypeEnum $type): Type
    {
        $className = self::$typesMap[$type->value] ?? self::loadDefaultType($type);

        if (!isset(self::$typeObjects[$className])) {
            $instance = new $className();
            assert($instance instanceof Type);
            self::$typeObjects[$className] = $instance;
        }

        return self::$typeObjects[$className];
    }

    /**
     * Get a Type instance based on the type of the passed php variable.
     */
    public static function getTypeFromPHPVariable(mixed $variable): ?Type
    {
        if (is_object($variable)) {
            if ($variable instanceof DateTimeInterface) {
                return self::getType(TypeEnum::TIMESTAMP);
            }
        } else {
            try {
                return self::getType(TypeEnum::from(gettype($variable)));
            } catch (ValueError) {
                return null;
            }
        }

        return null;
    }

    /**
     * Convert PHP to database value.
     */
    public static function convertPHPToDatabaseValue(mixed $value): mixed
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
     * @phpstan-param class-string<Type> $className
     */
    public static function setType(TypeEnum $type, string $className): void
    {
        self::$typesMap[$type->value] = $className;
    }

    public function __toString(): string
    {
        $type = static::class;
        $position = strrpos($type, '\\');

        if ($position !== false) {
            $type = substr($type, $position);
        }

        return str_replace('Type', '', $type);
    }

    private static function loadDefaultType(TypeEnum $type): string
    {
        $result = match ($type) {
            TypeEnum::TIMESTAMP => TimestampType::class,
            TypeEnum::STRING => StringType::class,
            TypeEnum::BOOLEAN => BooleanType::class,
            TypeEnum::FLOAT => FloatType::class,
            TypeEnum::INTEGER => IntegerType::class,
        };

        self::$typesMap[$type->value] = $result;

        return $result;
    }
}
