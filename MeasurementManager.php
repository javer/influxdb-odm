<?php

namespace Javer\InfluxDB\ODM;

use Doctrine\Persistence\ObjectManager;
use InfluxDB\Database;
use Javer\InfluxDB\ODM\Connection\ConnectionFactoryInterface;
use Javer\InfluxDB\ODM\Hydrator\ArrayHydrator;
use Javer\InfluxDB\ODM\Hydrator\HydratorInterface;
use Javer\InfluxDB\ODM\Hydrator\ObjectHydrator;
use Javer\InfluxDB\ODM\Hydrator\ScalarHydrator;
use Javer\InfluxDB\ODM\Hydrator\SingleScalarHydrator;
use Javer\InfluxDB\ODM\Mapping\ClassMetadata;
use Javer\InfluxDB\ODM\Mapping\ClassMetadataFactory;
use Javer\InfluxDB\ODM\Mapping\Driver\AnnotationDriver;
use Javer\InfluxDB\ODM\Persister\MeasurementPersister;
use Javer\InfluxDB\ODM\Query\Query;
use Javer\InfluxDB\ODM\Repository\MeasurementRepository;
use Javer\InfluxDB\ODM\Repository\RepositoryFactoryInterface;
use Javer\InfluxDB\ODM\Types\Type;
use RuntimeException;

class MeasurementManager implements ObjectManager
{
    /**
     * @var ClassMetadataFactory<ClassMetadata<object>>
     */
    private ClassMetadataFactory $metadataFactory;

    private ConnectionFactoryInterface $connectionFactory;

    private RepositoryFactoryInterface $repositoryFactory;

    private MeasurementPersister $measurementPersister;

    private string $url;

    /**
     * MeasurementManager constructor.
     *
     * @param AnnotationDriver           $annotationDriver
     * @param ConnectionFactoryInterface $connectionFactory
     * @param RepositoryFactoryInterface $repositoryFactory
     * @param string                     $url
     */
    public function __construct(
        AnnotationDriver $annotationDriver,
        ConnectionFactoryInterface $connectionFactory,
        RepositoryFactoryInterface $repositoryFactory,
        string $url
    )
    {
        $this->metadataFactory = new ClassMetadataFactory($annotationDriver);
        $this->repositoryFactory = $repositoryFactory;
        $this->connectionFactory = $connectionFactory;
        $this->measurementPersister = new MeasurementPersister($this);
        $this->url = $url;
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-return ClassMetadataFactory<ClassMetadata<object>>
     * @phpstan-ignore-next-line The method returns what is declared
     */
    public function getMetadataFactory(): ClassMetadataFactory
    {
        return $this->metadataFactory;
    }

    /**
     * @phpstan-template T of object
     * @phpstan-param    class-string<T> $className
     * @phpstan-return   ClassMetadata<T>
     */
    public function getClassMetadata(string $className): ClassMetadata
    {
        // @phpstan-ignore-next-line: It returns ClassMetadata<T>
        return $this->metadataFactory->getMetadataFor($className);
    }

    /**
     * Returns database.
     *
     * @return Database
     */
    public function getDatabase(): Database
    {
        return $this->connectionFactory->createConnection($this->url);
    }

    /**
     * Creates a new query.
     *
     * @param string $className
     *
     * @return Query
     *
     * @phpstan-template T of object
     * @phpstan-param    class-string<T> $className
     * @phpstan-return   Query<T>
     */
    public function createQuery(string $className): Query
    {
        return new Query($this, $className);
    }

    /**
     * Load types.
     *
     * @param array $types
     *
     * @phpstan-param array<string, array{class: class-string<Type>}> $types
     */
    public static function loadTypes(array $types): void
    {
        foreach ($types as $typeName => $typeConfig) {
            if (Type::hasType($typeName)) {
                Type::overrideType($typeName, $typeConfig['class']);
            } else {
                Type::addType($typeName, $typeConfig['class']);
            }
        }
    }

    /**
     * Create a new Hydrator for the className.
     *
     * @param string  $className
     * @param integer $hydrationMode
     *
     * @return HydratorInterface
     *
     * @throws RuntimeException
     *
     * @phpstan-param class-string $className
     */
    public function createHydrator(string $className, int $hydrationMode = Query::HYDRATE_OBJECT): HydratorInterface
    {
        $classMetadata = $this->getClassMetadata($className);

        switch ($hydrationMode) {
            case Query::HYDRATE_OBJECT:
                return new ObjectHydrator($classMetadata);

            case Query::HYDRATE_ARRAY:
                return new ArrayHydrator($classMetadata);

            case Query::HYDRATE_SCALAR:
                return new ScalarHydrator($classMetadata);

            case Query::HYDRATE_SINGLE_SCALAR:
                return new SingleScalarHydrator($classMetadata);

            default:
                throw new RuntimeException(sprintf('Unknown hydration mode: %d', $hydrationMode));
        }
    }

    /**
     * @param string $className
     * @param mixed  $id
     *
     * @phpstan-template T of object
     * @phpstan-param    class-string<T> $className
     * @phpstan-return   ?T
     */
    public function find(string $className, $id): ?object
    {
        return $this->getRepository($className)->find($id);
    }

    public function persist(object $object): void
    {
        $this->measurementPersister->persist([$object]);
    }

    /**
     * Persist all objects.
     *
     * @param iterable $objects
     *
     * @phpstan-param iterable<object> $objects
     */
    public function persistAll(iterable $objects): void
    {
        $this->measurementPersister->persist($objects);
    }

    public function remove(object $object): void
    {
        $this->measurementPersister->remove($object);
    }

    public function clear(): void
    {
    }

    public function detach(object $object): void
    {
    }

    public function refresh(object $object): void
    {
    }

    public function flush(): void
    {
    }

    /**
     * @phpstan-template T of object
     * @phpstan-param    class-string<T> $className
     * @phpstan-return   MeasurementRepository<T>
     */
    public function getRepository(string $className): MeasurementRepository
    {
        return $this->repositoryFactory->getRepository($this, $className);
    }

    public function initializeObject(object $obj): void
    {
    }

    public function contains(object $object): bool
    {
        return true;
    }
}
