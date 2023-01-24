<?php

namespace Javer\InfluxDB\ODM\Model;

use DateTime;
use InfluxDB2\Model\DeletePredicateRequest;

final class DeletionPoint
{
    /**
     * @param string   $measurement Measurement name of point to delete.
     * @param float    $time        UNIX timestamp with decimal nanoseconds.
     * @param string[] $tags        Key-value pairs of tag names and tag values.
     *
     * @phpstan-param array<string, string> $tags
     */
    public function __construct(
        private readonly string $measurement,
        private readonly float $time,
        private readonly array $tags,
    )
    {
    }

    public function toDeletePredicate(): DeletePredicateRequest
    {
        $dateTime = new DateTime(sprintf('@%f', $this->time));

        return new DeletePredicateRequest([
            'start' => $dateTime->format('Y-m-d\TH:i:s.u000\Z'),
            'stop' => $dateTime->format('Y-m-d\TH:i:s.u001\Z'),
            'predicate' => $this->buildDeletePredicate(),
        ]);
    }

    private function buildDeletePredicate(): string
    {
        $tags = ['_measurement' => $this->measurement] + $this->tags;

        return implode(
            ' AND ',
            array_map(
                static fn(string $key, string $value): string => sprintf('"%s"="%s"', $key, $value),
                array_keys($tags),
                $tags,
            ),
        );
    }
}
