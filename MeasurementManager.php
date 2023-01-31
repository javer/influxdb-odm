<?php

namespace Javer\InfluxDB\ODM;

use Doctrine\Persistence\ObjectManager;
use Javer\InfluxDB\ODM\Client\ClientFactoryInterface;
use Javer\InfluxDB\ODM\Client\ClientInterface;
use Javer\InfluxDB\ODM\Hydrator\ArrayHydrator;
use Javer\InfluxDB\ODM\Hydrator\HydratorInterface;
use Javer\InfluxDB\ODM\Hydrator\ObjectHydrator;
use Javer\InfluxDB\ODM\Hydrator\ScalarHydrator;
use Javer\InfluxDB\ODM\Hydrator\SingleScalarHydrator;
use Javer\InfluxDB\ODM\Logger\InfluxLoggerInterface;
use Javer\InfluxDB\ODM\Mapping\ClassMetadata;
use Javer\InfluxDB\ODM\Mapping\ClassMetadataFactory;
use Javer\InfluxDB\ODM\Mapping\Driver\AttributeDriver;
use Javer\InfluxDB\ODM\Persister\MeasurementPersister;
use Javer\InfluxDB\ODM\Query\Query;
use Javer\InfluxDB\ODM\Repository\MeasurementRepository;
use Javer\InfluxDB\ODM\Repository\RepositoryFactoryInterface;
use Javer\InfluxDB\ODM\Types\Type;
use Javer\InfluxDB\ODM\Types\TypeEnum;
use RuntimeException;
use ValueError;

final class MeasurementManager implements ObjectManager
{
    /**
     * @var ClassMetadataFactory<object>
     */
    private readonly ClassMetadataFactory $metadataFactory;

    private readonly MeasurementPersister $measurementPersister;

    private readonly ClientInterface $client;

    public function __construct(
        AttributeDriver $attributeDriver,
        private readonly ClientFactoryInterface $clientFactory,
        private readonly RepositoryFactoryInterface $repositoryFactory,
        private readonly string $dsn,
        private readonly string $writePrecision,
        private readonly ?InfluxLoggerInterface $logger,
    )
    {
        $this->metadataFactory = new ClassMetadataFactory($attributeDriver);
        $this->measurementPersister = new MeasurementPersister($this);
        $this->client = $this->clientFactory->createClient($this->dsn, $this->writePrecision, $this->logger);
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-return ClassMetadataFactory<object>
     * @phpstan-ignore-next-line The method returns what is declared
     */
    public function getMetadataFactory(): ClassMetadataFactory
    {
        return $this->metadataFactory;
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-template T of object
     *
     * @phpstan-param class-string<T> $className
     *
     * @phpstan-return ClassMetadata<T>
     */
    public function getClassMetadata(string $className): ClassMetadata
    {
        // @phpstan-ignore-next-line: It returns ClassMetadata<T>
        return $this->metadataFactory->getMetadataFor($className);
    }

    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * Creates a new query.
     *
     * @phpstan-template T of object
     *
     * @phpstan-param class-string<T> $className
     *
     * @phpstan-return Query<T>
     */
    public function createQuery(string $className): Query
    {
        return new Query($this, $className);
    }

    /**
     * Load types.
     *
     * @param mixed[] $types
     *
     * @phpstan-param array<string, array{class: class-string<Type>}> $types
     */
    public static function loadTypes(array $types): void
    {
        foreach ($types as $typeName => $typeConfig) {
            try {
                Type::setType(TypeEnum::from($typeName), $typeConfig['class']);
            } catch (ValueError) {
                // Ignore invalid types.
            }
        }
    }

    /**
     * Create a new Hydrator for the className.
     *
     * @throws RuntimeException
     *
     * @phpstan-param class-string $className
     */
    public function createHydrator(string $className, int $hydrationMode = Query::HYDRATE_OBJECT): HydratorInterface
    {
        $classMetadata = $this->getClassMetadata($className);

        return match ($hydrationMode) {
            Query::HYDRATE_OBJECT => new ObjectHydrator($classMetadata),
            Query::HYDRATE_ARRAY => new ArrayHydrator($classMetadata),
            Query::HYDRATE_SCALAR => new ScalarHydrator($classMetadata),
            Query::HYDRATE_SINGLE_SCALAR => new SingleScalarHydrator($classMetadata),
            default => throw new RuntimeException(sprintf('Unknown hydration mode: %d', $hydrationMode)),
        };
    }

    /**
     * {@inheritdoc}
     *
     * @phpstan-template T of object
     *
     * @phpstan-param class-string<T> $className
     *
     * @phpstan-return ?T
     */
    public function find(string $className, mixed $id): ?object
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
     * {@inheritdoc}
     *
     * @phpstan-template T of object
     *
     * @phpstan-param class-string<T> $className
     *
     * @phpstan-return MeasurementRepository<T>
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
