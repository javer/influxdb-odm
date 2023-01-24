<?php

namespace Javer\InfluxDB\ODM\Client;

use InfluxDB2\Model\WritePrecision;
use Javer\InfluxDB\ODM\InfluxDBException;
use ValueError;

final class Config
{
    private const DEFAULT_PORT = 8086;

    public function __construct(
        private readonly SchemaEnum $schema,
        private readonly string $host,
        private readonly string $database,
        private readonly int $port = self::DEFAULT_PORT,
        private readonly string $user = '',
        private readonly string $pass = '',
        private readonly string $precision = WritePrecision::NS,
    )
    {
    }

    public static function fromDSN(string $dsn, string $precision = WritePrecision::NS): self
    {
        $configs = parse_url($dsn);

        if (!is_array($configs)) {
            throw new InfluxDBException("Couldn't parse DSN.");
        }

        if (!isset($configs['scheme'])) {
            throw new InfluxDBException('Missing "scheme" in DSN.');
        }

        try {
            $scheme = SchemaEnum::from($configs['scheme']);
        } catch (ValueError) {
            throw new InfluxDBException(sprintf(
                'Invalid "scheme" in DSN, possible values are: %s.',
                implode(
                    ', ',
                    array_map(static fn(SchemaEnum $enum): string => '"' . $enum->value . '"', SchemaEnum::cases()),
                ),
            ));
        }

        if (!isset($configs['host'])) {
            throw new InfluxDBException('Missing "host" in DSN.');
        }

        if (!isset($configs['path'])) {
            throw new InfluxDBException('Missing "database" in DSN.');
        }

        $database = ltrim((string) $configs['path'], '/');

        return new self(
            $scheme,
            (string) $configs['host'],
            $database,
            (int) ($configs['port'] ?? self::DEFAULT_PORT),
            (string) ($configs['user'] ?? ''),
            (string) ($configs['pass'] ?? ''),
            $precision,
        );
    }

    public function getSchema(): SchemaEnum
    {
        return $this->schema;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPass(): string
    {
        return $this->pass;
    }

    public function getPrecision(): string
    {
        return $this->precision;
    }

    public function getUrl(): string
    {
        return sprintf(
            '%s://%s:%d',
            match ($this->schema) {
                SchemaEnum::HTTP => 'http',
                SchemaEnum::HTTPS => 'https',
            },
            $this->host,
            $this->port,
        );
    }

    public function getBucket(): string
    {
        return $this->database . '/';
    }
}
