<?php

namespace Javer\InfluxDB\ODM\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Tag extends Field
{
    public ?bool $tag = true;

    public function __construct(
        ?string $name = null,
        ?string $type = 'string',
    )
    {
        parent::__construct($name, $type);
    }
}
