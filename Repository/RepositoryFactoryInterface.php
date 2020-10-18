<?php

namespace Javer\InfluxDB\ODM\Repository;

use Javer\InfluxDB\ODM\MeasurementManager;

/**
 * Interface RepositoryFactory
 *
 * @package Javer\InfluxDB\ODM\Repository
 */
interface RepositoryFactoryInterface
{
    /**
     * Get repository for the className.
     *
     * @param MeasurementManager $measurementManager
     * @param string             $className
     *
     * @return MeasurementRepository
     */
    public function getRepository(MeasurementManager $measurementManager, string $className): MeasurementRepository;
}
