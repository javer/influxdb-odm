<?php

namespace Javer\InfluxDB\ODM\Hydrator;

/**
 * @template T of object
 *
 * @template-extends AbstractHydrator<T>
 */
final class ObjectHydrator extends AbstractHydrator
{
    /**
     * {@inheritdoc}
     *
     * @phpstan-return T
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
                    $this->classMetadata->getFieldPhpValue($fieldName, $value),
                );
            }
        }

        return $instance;
    }
}
