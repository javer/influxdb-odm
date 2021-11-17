<?php

namespace Javer\InfluxDB\ODM\Mapping;

use BadMethodCallException;
use Doctrine\Instantiator\Instantiator;
use Doctrine\Instantiator\InstantiatorInterface;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\Persistence\Mapping\ReflectionService;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use InvalidArgumentException;
use Javer\InfluxDB\ODM\Types\Type;
use ReflectionClass;
use ReflectionProperty;

/**
 * Class ClassMetadata
 *
 * A <tt>ClassMetadata</tt> instance holds all the object-measurement mapping metadata
 * of a measurement and its associations.
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
 * @template-implements BaseClassMetadata<T>
 */
final class ClassMetadata implements BaseClassMetadata
{
    /**
     * READ-ONLY: The name of the InfluxDB database the measurement is mapped to.
     *
     * @var string|null
     */
    public ?string $db;

    /**
     * READ-ONLY: The name of the InfluxDB measurement the measurement is mapped to.
     *
     * @var string
     */
    public string $measurement;

    /**
     * READ-ONLY: The field name of the measurement identifier.
     *
     * @var string|null
     */
    public ?string $identifier;

    /**
     * READ-ONLY: The name of the measurement class.
     *
     * @var string
     *
     * @phpstan-var class-string<T>
     */
    public string $name;

    /**
     * The name of the custom repository class used for the measurement class.
     *
     * @var string|null
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
     * - <b>fieldName</b> (string)
     * The name of the field in the Measurement.
     *
     * - <b>id</b> (boolean, optional)
     * Marks the field as the primary key of the measurement.
     * Multiple fields of a measurement can have the id attribute, forming a composite key.
     *
     * @var array
     *
     * @phpstan-var array<string, array{
     *      type: string,
     *      fieldName: string,
     *      name: string,
     *      id?: bool,
     *      precision?: string,
     *      countable?: bool,
     *      tag?: bool,
     * }>
     */
    public array $fieldMappings = [];

    /**
     * READ-ONLY: An array of field names. Used to look up field names from column names.
     * Keys are column names and values are field names.
     *
     * @var array<string>
     */
    public array $fieldNames = [];

    public ?string $countableFieldName = null;

    /**
     * READ-ONLY: Whether this class describes the mapping of a mapped superclass.
     *
     * @var boolean
     */
    public bool $isMappedSuperclass = false;

    /**
     * The ReflectionClass instance of the mapped class.
     *
     * @var ReflectionClass
     *
     * @phpstan-var ReflectionClass<T>
     */
    public ReflectionClass $reflClass;

    private InstantiatorInterface $instantiator;

    private ReflectionService $reflectionService;

    /**
     * ClassMetadata constructor.
     *
     * @param string $measurementClassName
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

    /**
     * {@inheritDoc}
     */
    public function isIdentifier($fieldName): bool
    {
        return $this->identifier === $fieldName;
    }

    /**
     * Sets the mapped identifier field of this class.
     *
     * @param string|null $identifier
     */
    public function setIdentifier(?string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): array
    {
        return [$this->identifier];
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierFieldNames(): array
    {
        return $this->identifier ? [$this->identifier] : [];
    }

    /**
     * {@inheritDoc}
     */
    public function hasField($fieldName): bool
    {
        return isset($this->fieldMappings[$fieldName]);
    }

    /**
     * Gets the ReflectionProperties of the mapped class.
     *
     * @return ReflectionProperty[]
     */
    public function getReflectionProperties(): array
    {
        return $this->reflFields;
    }

    /**
     * Gets a ReflectionProperty for a specific field of the mapped class.
     *
     * @param string $name
     *
     * @return ReflectionProperty
     */
    public function getReflectionProperty(string $name): ReflectionProperty
    {
        return $this->reflFields[$name];
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

    /**
     * Returns the database this Measurement is mapped to.
     *
     * @return string|null
     */
    public function getDatabase(): ?string
    {
        return $this->db;
    }

    /**
     * Set the database this Measurement is mapped to.
     *
     * @param string|null $db
     */
    public function setDatabase(?string $db): void
    {
        $this->db = $db;
    }

    /**
     * Get the collection this Measurement is mapped to.
     *
     * @return string
     */
    public function getMeasurement(): string
    {
        return $this->measurement;
    }

    /**
     * Sets the collection this Measurement is mapped to.
     *
     * @param string $name
     *
     * @throws InvalidArgumentException
     */
    public function setMeasurement(string $name): void
    {
        $this->measurement = $name;
    }

    /**
     * Set customRepositoryClassName.
     *
     * @param string|null $repositoryClassName
     */
    public function setCustomRepositoryClassName(?string $repositoryClassName): void
    {
        $this->customRepositoryClassName = $repositoryClassName;
    }

    /**
     * {@inheritDoc}
     */
    public function hasAssociation($fieldName): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isSingleValuedAssociation($fieldName): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isCollectionValuedAssociation($fieldName): bool
    {
        return false;
    }

    /**
     * Casts the identifier to its portable PHP type.
     *
     * @param mixed $id
     *
     * @return mixed $id
     */
    public function getPHPIdentifierValue($id)
    {
        $idType = $this->fieldMappings[$this->identifier]['type'];

        return Type::getType($idType)->convertToPHPValue($id);
    }

    /**
     * Casts the identifier to its database type.
     *
     * @param mixed $id
     *
     * @return mixed $id
     */
    public function getDatabaseIdentifierValue($id)
    {
        $idType = $this->fieldMappings[$this->identifier]['type'];

        return Type::getType($idType)->convertToDatabaseValue($id);
    }

    /**
     * Sets the measurement identifier of a measurement.
     *
     * The value will be converted to a PHP type before being set.
     *
     * @param object $measurement
     * @param mixed  $id
     */
    public function setIdentifierValue(object $measurement, $id): void
    {
        $id = $this->getPHPIdentifierValue($id);

        $this->reflFields[$this->identifier]->setValue($measurement, $id);
    }

    /**
     * Gets the measurement identifier as a PHP type.
     *
     * @param object $measurement
     *
     * @return mixed
     */
    public function getIdentifierValue(object $measurement)
    {
        return $this->reflFields[$this->identifier]->getValue($measurement);
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierValues($object): array
    {
        return [$this->identifier => $this->getIdentifierValue($object)];
    }

    /**
     * Get the measurement identifier object as a database type.
     *
     * @param object $measurement
     *
     * @return mixed
     */
    public function getIdentifierObject(object $measurement)
    {
        return $this->getDatabaseIdentifierValue($this->getIdentifierValue($measurement));
    }

    /**
     * Sets the specified field to the specified value on the given measurement.
     *
     * @param object $measurement
     * @param string $field
     * @param mixed  $value
     */
    public function setFieldValue(object $measurement, string $field, $value): void
    {
        $this->reflFields[$field]->setValue($measurement, $value);
    }

    /**
     * Gets the specified field's value off the given measurement.
     *
     * @param object $measurement
     * @param string $field
     *
     * @return mixed
     */
    public function getFieldValue(object $measurement, string $field)
    {
        return $this->reflFields[$field]->getValue($measurement);
    }

    /**
     * Gets the mapping of a field.
     *
     * @param string $fieldName
     *
     * @return array
     *
     * @throws MappingException
     *
     * @phpstan-return array{
     *      type: string,
     *      fieldName: string,
     *      name: string,
     *      id?: bool,
     *      precision?: string,
     *      countable?: bool,
     *      tag?: bool,
     * }
     */
    public function getFieldMapping(string $fieldName): array
    {
        if (!isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }

        return $this->fieldMappings[$fieldName];
    }

    /**
     * Gets the field name for a column name.
     * If no field name can be found the column name is returned.
     *
     * @param string $columnName The column name.
     *
     * @return string The column alias.
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

    /**
     * {@inheritDoc}
     */
    public function getTypeOfField($fieldName): ?string
    {
        return isset($this->fieldMappings[$fieldName]) ? $this->fieldMappings[$fieldName]['type'] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationTargetClass($assocName): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @throws BadMethodCallException
     */
    public function isAssociationInverseSide($fieldName): bool
    {
        throw new BadMethodCallException(__METHOD__ . '() is not implemented yet.');
    }

    /**
     * {@inheritDoc}
     *
     * @throws BadMethodCallException
     */
    public function getAssociationMappedByTargetField($fieldName)
    {
        throw new BadMethodCallException(__METHOD__ . '() is not implemented yet.');
    }

    /**
     * Map a field.
     *
     * @param array $mapping
     *
     * @throws MappingException
     *
     * @phpstan-param array{
     *      type: string,
     *      fieldName?: string,
     *      name?: string,
     *      id?: bool,
     *      precision?: string,
     *      countable?: bool,
     *      tag?: bool,
     * } $mapping
     */
    public function mapField(array $mapping): void
    {
        if (!isset($mapping['fieldName']) && isset($mapping['name'])) {
            $mapping['fieldName'] = $mapping['name'];
        }

        if (!isset($mapping['fieldName']) || !is_string($mapping['fieldName'])) {
            throw MappingException::missingFieldName($this->name);
        }

        if (!isset($mapping['name'])) {
            $mapping['name'] = $mapping['fieldName'];
        }

        if (isset($mapping['id']) && $mapping['id'] === true) {
            $this->identifier = $mapping['fieldName'];
        }

        if (isset($mapping['countable']) && $mapping['countable'] === true) {
            if (($mapping['id'] ?? false) || ($mapping['tag'] ?? false)) {
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
     * @return array The names of all the fields that should be serialized.
     */
    public function __sleep(): array
    {
        $serialized = [
            'fieldMappings',
            'fieldNames',
            'identifier',
            'name',
            'db',
            'measurement',
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
            $prop = $this->reflectionService->getAccessibleProperty($mapping['declared'] ?? $this->name, $field);
            assert($prop instanceof ReflectionProperty);
            $this->reflFields[$field] = $prop;
        }
    }

    /**
     * Creates a new instance of the mapped class, without invoking the constructor.
     *
     * @return object
     *
     * @phpstan-return T
     */
    public function newInstance(): object
    {
        // @phpstan-ignore-next-line: doctrine/instantiator is not fully PHPStan compliant
        return $this->instantiator->instantiate($this->name);
    }

    /**
     * Returns field database name.
     *
     * @param string $fieldName
     *
     * @return string
     */
    public function getFieldDatabaseName(string $fieldName): string
    {
        $fieldMapping = $this->getFieldMapping($fieldName);

        return $fieldMapping['name'];
    }

    /**
     * Returns field database value.
     *
     * @param string $fieldName
     * @param mixed  $value
     *
     * @return mixed
     */
    public function getFieldDatabaseValue(string $fieldName, $value)
    {
        $fieldMapping = $this->getFieldMapping($fieldName);

        return Type::getType($fieldMapping['type'])->convertToDatabaseValue($value);
    }

    /**
     * Returns field PHP value.
     *
     * @param string $fieldName
     * @param mixed  $value
     *
     * @return mixed
     */
    public function getFieldPhpValue(string $fieldName, $value)
    {
        $fieldMapping = $this->getFieldMapping($fieldName);

        return Type::getType($fieldMapping['type'])->convertToPHPValue($value);
    }

    /**
     * Returns countable field name.
     *
     * @return string
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
