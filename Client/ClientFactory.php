<?php

namespace Javer\InfluxDB\ODM\Client;

use InfluxDB2\Client as InfluxClient;
use InfluxDB2\Model\WritePrecision;
use Javer\InfluxDB\ODM\Logger\InfluxLoggerInterface;

final class ClientFactory implements ClientFactoryInterface
{
    /**
     * @var array<string, Client>
     */
    private array $clients = [];

    public function createClient(
        string $dsn,
        string $precision = WritePrecision::NS,
        ?InfluxLoggerInterface $logger = null,
    ): Client
    {
        $config = Config::fromDSN($dsn, $precision);
        $url = $config->getUrl();
        $bucket = $config->getBucket();

        return $this->clients[$this->getHash($url, $bucket, $precision)]
            ?? $this->buildClient($url, $bucket, $precision, $logger);
    }

    private function buildClient(string $url, string $bucket, string $precision, ?InfluxLoggerInterface $logger): Client
    {
        $client = new Client(new InfluxClient([
            'url' => $url,
            'bucket' => $bucket,
            'token' => '',
            'org' => '-',
            'precision' => $precision,
        ]), $bucket, $logger);

        $this->clients[$this->getHash($url, $bucket, $precision)] = $client;

        return $client;
    }

    private function getHash(string $url, string $bucket, string $precision): string
    {
        return md5(sprintf('%s/%s/%s', $url, $bucket, $precision));
    }
}
