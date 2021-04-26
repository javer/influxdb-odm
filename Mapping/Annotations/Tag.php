<?php

namespace Javer\InfluxDB\ODM\Mapping\Annotations;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Tag extends Field
{
    public ?string $type = 'string';

    public ?bool $tag = true;
}
