<?php

namespace Javer\InfluxDB\ODM\Mapping\Annotations;

/**
 * Class Tag
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @package Javer\InfluxDB\ODM\Mapping\Annotations
 */
final class Tag extends Field
{
    public ?string $type = 'string';

    public ?bool $tag = true;
}
