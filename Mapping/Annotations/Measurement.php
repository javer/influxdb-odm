<?php

namespace Javer\InfluxDB\ODM\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Measurement implements Annotation
{
    public ?string $name = null;

    public ?string $repositoryClass = null;

    public function __construct(
        ?string $name = null,
        ?string $repositoryClass = null,
    )
    {
        $this->name = $name;
        $this->repositoryClass = $repositoryClass;
    }
}
