<?php

namespace Javer\InfluxDB\ODM\Hydrator;

/**
 * @template T of object
 *
 * @template-extends AbstractHydrator<T>
 */
final class ScalarHydrator extends AbstractHydrator
{
    /**
     * {@inheritdoc}
     *
     * @phpstan-return array<string, mixed>
     */
    public function hydrate(array $data): array
    {
        $result = [];

        foreach ($data as $name => $value) {
            $fieldName = $this->classMetadata->getFieldName($name);

            if ($this->classMetadata->hasField($fieldName)) {
                $result[$fieldName] = $value;
            }
        }

        return $result;
    }
}
