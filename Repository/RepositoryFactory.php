<?php

namespace Javer\InfluxDB\ODM\Repository;

use Javer\InfluxDB\ODM\MeasurementManager;
use RuntimeException;

class RepositoryFactory implements RepositoryFactoryInterface
{
    /**
     * @var MeasurementRepository[]
     *
     * @phpstan-var array<string, MeasurementRepository>
     * @phpstan-ignore-next-line: Unable to specify T for MeasurementRepository because it is hashmap for all classes
     */
    private array $repositories = [];

    /**
     * {@inheritDoc}
     *
     * @phpstan-template T of object
     * @phpstan-param    class-string<T> $className
     * @phpstan-return   MeasurementRepository<T>
     */
    public function getRepository(MeasurementManager $measurementManager, string $className): MeasurementRepository
    {
        $repositoryHash = $measurementManager->getClassMetadata($className)->getName();

        return $this->repositories[$repositoryHash]
            ?? ($this->repositories[$repositoryHash] = $this->createRepository($measurementManager, $className));
    }

    /**
     * Creates a new repository.
     *
     * @param MeasurementManager $measurementManager
     * @param string             $className
     *
     * @return MeasurementRepository
     *
     * @throws RuntimeException
     *
     * @phpstan-template T of object
     * @phpstan-param    class-string<T> $className
     * @phpstan-return   MeasurementRepository<T>
     */
    private function createRepository(
        MeasurementManager $measurementManager,
        string $className
    ): MeasurementRepository
    {
        $classMetadata = $measurementManager->getClassMetadata($className);

        /** @phpstan-var MeasurementRepository<T> $repositoryClassName */
        $repositoryClassName = $classMetadata->customRepositoryClassName ?? MeasurementRepository::class;

        return new $repositoryClassName($measurementManager, $className);
    }
}
