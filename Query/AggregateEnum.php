<?php

namespace Javer\InfluxDB\ODM\Query;

enum AggregateEnum: string
{
    case COUNT = 'count';
    case MEDIAN = 'median';
    case MEAN = 'mean';
    case SUM = 'sum';
    case FIRST = 'first';
    case LAST = 'last';
}
