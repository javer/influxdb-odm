<?php

namespace Javer\InfluxDB\ODM\Client;

use DateTime;
use InfluxDB2\Client as InfluxClient;
use InfluxDB2\FluxTable;
use InfluxDB2\Model\DeletePredicateRequest;
use InfluxDB2\ObjectSerializer;
use InfluxDB2\QueryApi;
use InfluxDB2\Service\DeleteService;
use InfluxDB2\WriteApi;
use InfluxDB2\WritePayloadSerializer;
use Javer\InfluxDB\ODM\Logger\InfluxLoggerInterface;
use Throwable;

final class Client implements ClientInterface
{
    private ?QueryApi $queryApi = null;

    private ?WriteApi $writeApi = null;

    private ?DeleteService $deleteService = null;

    public function __construct(
        private readonly InfluxClient $client,
        private readonly string $database,
        private readonly ?InfluxLoggerInterface $logger,
    )
    {
    }

    /**
     * {@inheritDoc}
     *
     * @throws Throwable
     */
    public function query(string $query): ?FluxTable
    {
        $startTime = microtime(true);

        try {
            $result = $this->getQueryApi()->query($query)[0] ?? null;
        } catch (Throwable $e) {
            $exception = $e;

            throw $e;
        } finally {
            $endTime = microtime(true);

            $this->logger?->logWrite(
                $query,
                $endTime - $startTime,
                isset($result) ? count($result->records) : 0,
                $exception ?? null,
            );
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Throwable
     */
    public function write(array $data): void
    {
        $payload = WritePayloadSerializer::generatePayload($data);

        if (!is_string($payload)) {
            return;
        }

        $startTime = microtime(true);

        try {
            $this->getWriteApi()->writeRaw($payload);
        } catch (Throwable $e) {
            $exception = $e;

            throw $e;
        } finally {
            $endTime = microtime(true);

            $this->logger?->logWrite($payload, $endTime - $startTime, count($data), $exception ?? null);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws Throwable
     */
    public function delete(array $data): void
    {
        foreach ($data as $point) {
            $payload = $point->toDeletePredicate();
            $startTime = microtime(true);

            try {
                $this->postDelete($payload);
            } catch (Throwable $e) {
                $exception = $e;

                throw $e;
            } finally {
                $endTime = microtime(true);

                $this->logger?->logWrite(
                    json_encode(ObjectSerializer::sanitizeForSerialization($payload), JSON_THROW_ON_ERROR),
                    $endTime - $startTime,
                    1,
                        $exception ?? null,
                );
            }
        }
    }

    public function dropMeasurement(string $measurement): void
    {
        $this->postDelete(new DeletePredicateRequest([
            'start' => new DateTime('@0'),
            'stop' => new DateTime(),
            'predicate' => sprintf('"_measurement"="%s"', $measurement),
        ]));
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function createDeleteService(): DeleteService
    {
        $deleteService = $this->client->createService(DeleteService::class);
        assert($deleteService instanceof DeleteService);

        return $deleteService;
    }

    private function getQueryApi(): QueryApi
    {
        $queryApi = $this->queryApi ?? $this->client->createQueryApi();

        if ($this->queryApi === null) {
            $this->queryApi = $queryApi;
        }

        return $queryApi;
    }

    private function getWriteApi(): WriteApi
    {
        $writeApi = $this->writeApi ?? $this->client->createWriteApi();

        if ($this->writeApi === null) {
            $this->writeApi = $writeApi;
        }

        return $writeApi;
    }

    private function getDeleteService(): DeleteService
    {
        $deleteService = $this->deleteService ?? $this->createDeleteService();

        if ($this->deleteService === null) {
            $this->deleteService = $deleteService;
        }

        return $deleteService;
    }

    private function postDelete(DeletePredicateRequest $payload): void
    {
        $this->getDeleteService()->postDelete(
            $payload,
            org: $this->client->options['org'] ?? null,
            bucket: $this->client->options['bucket'] ?? null,
        );
    }
}
