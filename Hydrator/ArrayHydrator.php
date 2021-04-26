<?php

namespace Javer\InfluxDB\ODM\Hydrator;

/**
 * @template T of object
 * @template-extends  AbstractHydrator<T>
 */
class ArrayHydrator extends AbstractHydrator
{
    /**
     * Hydrate data from the database.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function hydrate(array $data)
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
