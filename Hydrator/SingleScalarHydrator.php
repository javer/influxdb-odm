<?php

namespace Javer\InfluxDB\ODM\Hydrator;

/**
 * @template T of object
 *
 * @template-extends AbstractHydrator<T>
 */
final class SingleScalarHydrator extends AbstractHydrator
{
    /**
     * {@inheritdoc}
     */
    public function hydrate(array $data): mixed
    {
        return $data['_value'] ?? null;
    }
}
