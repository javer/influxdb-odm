<?php

namespace Javer\InfluxDB\ODM\Repository;

use Javer\InfluxDB\ODM\MeasurementManager;
use RuntimeException;

/**
 * Class RepositoryFactory
 *
 * @package Javer\InfluxDB\ODM\Repository
 */
class RepositoryFactory implements RepositoryFactoryInterface
{
    /**
     * @var MeasurementRepository[]
     */
    private array $repositories = [];

    /**
     * {@inheritDoc}
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
     */
    private function createRepository(
        MeasurementManager $measurementManager,
        string $className
    ): MeasurementRepository
    {
        $classMetadata = $measurementManager->getClassMetadata($className);
        $repositoryClassName = $classMetadata->customRepositoryClassName ?? MeasurementRepository::class;

        return new $repositoryClassName($measurementManager, $className);
    }
}
