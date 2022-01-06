<?php

namespace Javer\InfluxDB\ODM\Mapping\Driver;

use Doctrine\Common\Annotations\Reader;

class AttributeDriver extends AnnotationDriver
{
    /**
     * @param string|string[]|null $paths
     * @param Reader|null          $reader
     */
    public function __construct($paths = null, ?Reader $reader = null)
    {
        parent::__construct($reader ?? new AttributeReader(), $paths);
    }

    /**
     * @param string[]|string $paths
     * @param Reader|null     $reader
     *
     * @return AttributeDriver
     */
    public static function create($paths = [], ?Reader $reader = null): AnnotationDriver
    {
        return new self($paths, $reader);
    }
}
