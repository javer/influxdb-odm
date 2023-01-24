<?php

namespace Javer\InfluxDB\ODM\Hydrator;

/**
 * @template T of object
 *
 * @template-extends AbstractHydrator<T>
 */
final class ArrayHydrator extends AbstractHydrator
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
                $result[$fieldName] = $this->classMetadata->getFieldPhpValue($fieldName, $value);
            }
        }

        return $result;
    }
}
