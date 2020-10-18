<?php

namespace Javer\InfluxDB\ODM\Iterator;

use Generator;
use Iterator;
use Javer\InfluxDB\ODM\Hydrator\HydratorInterface;
use RuntimeException;

/**
 * Class HydratingIterator
 *
 * @package Javer\InfluxDB\ODM\Iterator
 */
class HydratingIterator implements Iterator
{
    private ?Generator $iterator;

    private HydratorInterface $hydrator;

    /**
     * HydratingIterator constructor.
     *
     * @param iterable          $iterable
     * @param HydratorInterface $hydrator
     */
    public function __construct(iterable $iterable, HydratorInterface $hydrator)
    {
        $this->iterator = $this->wrapIterable($iterable);
        $this->hydrator = $hydrator;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->iterator = null;
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->hydrator->hydrate($this->getIterator()->current());
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->getIterator()->key();
    }

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        $this->getIterator()->next();
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        $this->getIterator()->rewind();
    }

    /**
     * {@inheritDoc}
     */
    public function valid(): bool
    {
        return $this->key() !== null;
    }

    /**
     * Returns iterator.
     *
     * @return Generator
     *
     * @throws RuntimeException
     */
    private function getIterator(): Generator
    {
        if ($this->iterator === null) {
            throw new RuntimeException('Iterator has already been destroyed');
        }

        return $this->iterator;
    }

    /**
     * Wraps iterable dataset into generator.
     *
     * @param iterable $iterable
     *
     * @return Generator
     */
    private function wrapIterable(iterable $iterable): Generator
    {
        foreach ($iterable as $key => $value) {
            yield $key => $value;
        }
    }
}
