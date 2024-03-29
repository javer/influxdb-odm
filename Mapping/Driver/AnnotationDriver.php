<?php

namespace Javer\InfluxDB\ODM\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;

class AnnotationDriver extends AttributeDriver
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading docblock annotations.
     *
     * @param Reader               $reader The AnnotationReader to use, duck-typed.
     * @param string|string[]|null $paths  One or multiple paths where mapping classes can be found.
     */
    public function __construct(Reader $reader, mixed $paths = null)
    {
        $this->reader = $reader;

        $this->addPaths((array) $paths);
    }

    /**
     * Factory method for the Annotation Driver
     *
     * @param string[]|string $paths
     * @param Reader|null     $reader
     *
     * @return AnnotationDriver
     */
    public static function create($paths = [], ?Reader $reader = null): AnnotationDriver
    {
        return new self($reader ?? new AnnotationReader(), $paths);
    }
}
