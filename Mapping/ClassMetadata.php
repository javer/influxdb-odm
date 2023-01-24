<?php

namespace Javer\InfluxDB\ODM\Mapping;

use BadMethodCallException;
use Doctrine\Instantiator\Instantiator;
use Doctrine\Instantiator\InstantiatorInterface;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\Persistence\Mapping\ReflectionService;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Javer\InfluxDB\ODM\Types\Type;
use Javer\InfluxDB\ODM\Types\TypeEnum;
use ReflectionClass;
use ReflectionProperty;

/**
 * A <tt>ClassMetadata</tt> instance holds all the object-measurement mapping metadata
 * of a measurement.
 *
 * Once populated, ClassMetadata instances are usually cached in a serialized form.
 *
 * <b>IMPORTANT NOTE:</b>
 *
 * The fields of this class are only public for 2 reasons:
 * 1) To allow fast READ access.
 * 2) To drastically reduce the size of a serialized instance (private/protected members
 *    get the whole class name, namespace inclusive, prepended to every property in
 *    the serialized representation).
 *
 * @template-covariant T of object
 *
 * @template-implements BaseClassMetadata<T>
 */
final class ClassMetadata implements BaseClassMetadata
{
    /**
     * READ-ONLY: The name of the InfluxDB measurement the measurement is mapped to.
     */
    public string $measurement;

    /**
     * READ-ONLY: The field name of the measurement identifier.
     */
    public ?string $identifier = null;

    /**
     * READ-ONLY: The name of the measurement class.
     *
     * @phpstan-var class-string<T>
     */
    public readonly string $name;

    /**
     * The name of the custom repository class used for the measurement class.
     */
    public ?string $customRepositoryClassName = null;

    /**
     * The ReflectionProperty instances of the mapped class.
     *
     * @var ReflectionProperty[]
     */
    public array $reflFields = [];

    /**
     * READ-ONLY: The field mappings of the class.
     * Keys are field names and values are mapping definitions.
     *
     * The mapping definition array has the following values:
     *
     * @var mixed[]
     *
     * @phpstan-var array<string, array{
     *      type: TypeEnum,
     *      fieldName: string,
     *      name: string,
     *      countable?: bool|null,
     *      tag?: bool|null,
     * }>
     */
    public array $fieldMappings = [];

    /**
     * READ-ONLY: An array of field names. Used to look up field names from column names.
     * Keys are column names and values are field names.
     *
     * @var string[]
     */
    public array $fieldNames = [];

    public ?string $countableFieldName = null;

    /**
     * The ReflectionClass instance of the mapped class.
     *
     * @phpstan-var ReflectionClass<T>
     */
    public ReflectionClass $reflClass;

    private InstantiatorInterface $instantiator;

    private ReflectionService $reflectionService;

    /**
     * Constructor.
     *
     * @phpstan-param class-string<T> $measurementClassName
     */
    public function __construct(string $measurementClassName)
    {
        $this->name = $measurementClassName;
        $this->reflectionService = new RuntimeReflectionService();
        $this->reflClass = new ReflectionClass($measurementClassName);
        $this->setMeasurement($this->reflClass->getShortName());
        $this->instantiator = new Instantiator();
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-return ReflectionClass<T>
     */
    public function getReflectionClass(): ReflectionClass
    {
        return $this->reflClass;
    }

    public function isIdentifier(string $fieldName): bool
    {
        return $this->identifier === $fieldName;
    }

    public function setIdentifier(?string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): array
    {
        return $this->getIdentifierFieldNames();
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierFieldNames(): array
    {
        return $this->identifier ? [$this->identifier] : [];
    }

    public function hasField(string $fieldName): bool
    {
        return isset($this->fieldMappings[$fieldName]);
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-return class-string<T>
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getMeasurement(): string
    {
        return $this->measurement;
    }

    public function setMeasurement(string $name): void
    {
        $this->measurement = $name;
    }

    public function setCustomRepositoryClassName(?string $repositoryClassName): void
    {
        $this->customRepositoryClassName = $repositoryClassName;
    }

    public function hasAssociation(string $fieldName): bool
    {
        return false;
    }

    public function isSingleValuedAssociation(string $fieldName): bool
    {
        return false;
    }

    public function isCollectionValuedAssociation(string $fieldName): bool
    {
        return false;
    }

    /**
     * Casts the identifier to its portable PHP type.
     */
    public function getPHPIdentifierValue(mixed $id): mixed
    {
        return Type::getType($this->fieldMappings[$this->identifier]['type'])->convertToPHPValue($id);
    }

    /**
     * Casts the identifier to its database type.
     */
    public function getDatabaseIdentifierValue(mixed $id): mixed
    {
        return Type::getType($this->fieldMappings[$this->identifier]['type'])->convertToDatabaseValue($id);
    }

    /**
     * Sets the measurement identifier of a measurement.
     *
     * The value will be converted to a PHP type before being set.
     */
    public function setIdentifierValue(object $measurement, mixed $id): void
    {
        $id = $this->getPHPIdentifierValue($id);

        $this->reflFields[$this->identifier]->setValue($measurement, $id);
    }

    /**
     * Gets the measurement identifier as a PHP type.
     */
    public function getIdentifierValue(object $measurement): mixed
    {
        return $this->reflFields[$this->identifier]->getValue($measurement);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierValues(object $object): array
    {
        return [$this->identifier => $this->getIdentifierValue($object)];
    }

    /**
     * Get the measurement identifier object as a database type.
     */
    public function getIdentifierObject(object $measurement): mixed
    {
        return $this->getDatabaseIdentifierValue($this->getIdentifierValue($measurement));
    }

    /**
     * Sets the specified field to the specified value on the given measurement.
     */
    public function setFieldValue(object $measurement, string $field, mixed $value): void
    {
        $this->reflFields[$field]->setValue($measurement, $value);
    }

    /**
     * Gets the specified field's value off the given measurement.
     */
    public function getFieldValue(object $measurement, string $field): mixed
    {
        return $this->reflFields[$field]->getValue($measurement);
    }

    /**
     * Gets the mapping of a field.
     *
     * @return mixed[]
     *
     * @throws MappingException
     *
     * @phpstan-return array{
     *      type: TypeEnum,
     *      fieldName: string,
     *      name: string,
     *      countable?: bool|null,
     *      tag?: bool|null,
     * }
     */
    public function getFieldMapping(string $fieldName): array
    {
        return $this->fieldMappings[$fieldName] ?? throw MappingException::mappingNotFound($this->name, $fieldName);
    }

    /**
     * Gets the field name for a column name.
     * If no field name can be found the column name is returned.
     */
    public function getFieldName(string $columnName): string
    {
        return $this->fieldNames[$columnName] ?? $columnName;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldNames(): array
    {
        return array_keys($this->fieldMappings);
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationNames(): array
    {
        return [];
    }

    public function getTypeOfField(string $fieldName): ?string
    {
        return isset($this->fieldMappings[$fieldName]) ? $this->fieldMappings[$fieldName]['type']->value : null;
    }

    public function getAssociationTargetClass(string $assocName): ?string
    {
        return null;
    }

    /**
     * @throws BadMethodCallException
     */
    public function isAssociationInverseSide(string $assocName): bool
    {
        throw new BadMethodCallException(__METHOD__ . '() is not implemented yet.');
    }

    /**
     * {@inheritDoc}
     *
     * @throws BadMethodCallException
     */
    public function getAssociationMappedByTargetField(string $assocName): string
    {
        throw new BadMethodCallException(__METHOD__ . '() is not implemented yet.');
    }

    /**
     * Map a field.
     *
     * @param mixed[] $mapping
     *
     * @throws MappingException
     *
     * @phpstan-param array{
     *      fieldName?: string|null,
     *      type?: TypeEnum|null,
     *      name?: string|null,
     *      countable?: bool|null,
     *      tag?: bool|null,
     * } $mapping
     */
    public function mapField(array $mapping): void
    {
        if (!isset($mapping['type'])) {
            throw MappingException::missingFieldType($this->name);
        }

        if (!isset($mapping['fieldName']) && isset($mapping['name'])) {
            $mapping['fieldName'] = $mapping['name'];
        }

        if (!isset($mapping['fieldName']) || !is_string($mapping['fieldName'])) {
            throw MappingException::missingFieldName($this->name);
        }

        if (!isset($mapping['name'])) {
            $mapping['name'] = $mapping['fieldName'];
        }

        if ($mapping['type'] === TypeEnum::TIMESTAMP) {
            if ($this->identifier !== null && $this->identifier !== $mapping['fieldName']) {
                MappingException::hasSeveralIdentifierFields($this->name);
            }

            $this->identifier = $mapping['fieldName'];
        }

        if (isset($mapping['countable']) && $mapping['countable'] === true) {
            if (($mapping['type'] === TypeEnum::TIMESTAMP) || ($mapping['tag'] ?? false)) {
                throw MappingException::tagOrIdCannotBeCountable($this->name, $mapping['fieldName']);
            }

            if ($this->countableFieldName !== null) {
                throw MappingException::hasSeveralCountableFields($this->name);
            }

            $this->countableFieldName = $mapping['fieldName'];
        }

        $this->fieldMappings[$mapping['fieldName']] = $mapping;
        $this->fieldNames[$mapping['name']] = $mapping['fieldName'];

        $reflProp = $this->reflectionService->getAccessibleProperty($this->name, $mapping['fieldName']);
        assert($reflProp instanceof ReflectionProperty);
        $this->reflFields[$mapping['fieldName']] = $reflProp;
    }

    /**
     * Determines which fields get serialized.
     *
     * It is only serialized what is necessary for best unserialization performance.
     * That means any metadata properties that are not set or empty or simply have
     * their default value are NOT serialized.
     *
     * Parts that are also NOT serialized because they can not be properly unserialized:
     *      - reflClass (ReflectionClass)
     *      - reflFields (ReflectionProperty array)
     *
     * @return string[] The names of all the fields that should be serialized.
     */
    public function __sleep(): array
    {
        $serialized = [
            'fieldMappings',
            'fieldNames',
            'identifier',
            'name',
            'measurement',
            'countableFieldName',
        ];

        if ($this->customRepositoryClassName) {
            $serialized[] = 'customRepositoryClassName';
        }

        return $serialized;
    }

    /**
     * Restores some state that can not be serialized/unserialized.
     */
    public function __wakeup(): void
    {
        // Restore ReflectionClass and properties
        $this->reflectionService = new RuntimeReflectionService();
        $this->reflClass         = new ReflectionClass($this->name);
        $this->instantiator      = new Instantiator();

        foreach ($this->fieldMappings as $field => $mapping) {
            $prop = $this->reflectionService->getAccessibleProperty($this->name, $field);
            assert($prop instanceof ReflectionProperty);
            $this->reflFields[$field] = $prop;
        }
    }

    /**
     * Creates a new instance of the mapped class, without invoking the constructor.
     *
     * @phpstan-return T
     */
    public function newInstance(): object
    {
        return $this->instantiator->instantiate($this->name);
    }

    public function getFieldDatabaseName(string $fieldName): string
    {
        $fieldMapping = $this->getFieldMapping($fieldName);

        return $fieldMapping['name'];
    }

    public function getFieldDatabaseValue(string $fieldName, mixed $value): mixed
    {
        $fieldMapping = $this->getFieldMapping($fieldName);

        return Type::getType($fieldMapping['type'])->convertToDatabaseValue($value);
    }

    public function getFieldPhpValue(string $fieldName, mixed $value): mixed
    {
        $fieldMapping = $this->getFieldMapping($fieldName);

        return Type::getType($fieldMapping['type'])->convertToPHPValue($value);
    }

    /**
     * Returns countable field name.
     *
     * @throws MappingException
     */
    public function getCountableFieldName(): string
    {
        $countableFieldName = $this->countableFieldName;

        if ($countableFieldName === null) {
            throw MappingException::missingCountableField($this->name);
        }

        return $countableFieldName;
    }
}
