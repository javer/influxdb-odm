<?php

namespace Javer\InfluxDB\ODM\Client;

enum SchemaEnum: string
{
    case HTTP = 'influxdb';
    case HTTPS = 'https+influxdb';
}
