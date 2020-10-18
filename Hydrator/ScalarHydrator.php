<?php

namespace Javer\InfluxDB\ODM\Hydrator;

/**
 * Class ScalarHydrator
 *
 * @package Javer\InfluxDB\ODM\Hydrator
 */
class ScalarHydrator extends AbstractHydrator
{
    /**
     * {@inheritDoc}
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
