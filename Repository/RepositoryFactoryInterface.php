<?php

namespace Javer\InfluxDB\ODM\Repository;

use Javer\InfluxDB\ODM\MeasurementManager;

interface RepositoryFactoryInterface
{
    /**
     * Get repository for the className.
     *
     * @phpstan-template T of object
     *
     * @phpstan-param class-string<T> $className
     *
     * @phpstan-return MeasurementRepository<T>
     */
    public function getRepository(MeasurementManager $measurementManager, string $className): MeasurementRepository;
}
