<?php

namespace Javer\InfluxDB\ODM\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Field implements Annotation
{
    public ?string $name = null;

    public ?string $type = null;

    public ?bool $countable = null;

    public function __construct(
        ?string $name = null,
        ?string $type = null,
        ?bool $countable = null,
    )
    {
        $this->name = $name;
        $this->type = $type;
        $this->countable = $countable;
    }
}
