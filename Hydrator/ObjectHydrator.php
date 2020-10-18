<?php

namespace Javer\InfluxDB\ODM\Hydrator;

/**
 * Class ObjectHydrator
 *
 * @package Javer\InfluxDB\ODM\Hydrator
 */
class ObjectHydrator extends AbstractHydrator
{
    /**
     * {@inheritDoc}
     */
    public function hydrate(array $data): object
    {
        $instance = $this->classMetadata->newInstance();

        foreach ($data as $name => $value) {
            $fieldName = $this->classMetadata->getFieldName($name);

            if ($this->classMetadata->hasField($fieldName)) {
                $this->classMetadata->setFieldValue(
                    $instance,
                    $fieldName,
                    $this->classMetadata->getFieldPhpValue($fieldName, $value)
                );
            }
        }

        return $instance;
    }
}
