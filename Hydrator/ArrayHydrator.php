<?php

namespace Javer\InfluxDB\ODM\Hydrator;

/**
 * Class ArrayHydrator
 *
 * @package Javer\InfluxDB\ODM\Hydrator
 */
class ArrayHydrator extends AbstractHydrator
{
    /**
     * {@inheritDoc}
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
