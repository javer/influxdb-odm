<?php

namespace Javer\InfluxDB\ODM\Types;

enum TypeEnum: string
{
    case BOOLEAN = 'boolean';
    case FLOAT = 'float';
    case INTEGER = 'integer';
    case STRING = 'string';
    case TIMESTAMP = 'timestamp';
}
