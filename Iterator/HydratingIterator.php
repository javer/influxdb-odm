<?php

namespace Javer\InfluxDB\ODM\Iterator;

use Generator;
use Iterator;
use Javer\InfluxDB\ODM\Hydrator\HydratorInterface;
use RuntimeException;

/**
 * @template-implements Iterator<mixed>
 */
class HydratingIterator implements Iterator
{
    /**
     * @phpstan-var Generator<int, mixed>|null
     */
    private ?Generator $iterator;

    private HydratorInterface $hydrator;

    /**
     * HydratingIterator constructor.
     *
     * @param iterable          $iterable
     * @param HydratorInterface $hydrator
     *
     * @phpstan-param iterable<int, mixed> $iterable
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
    public function current(): mixed
    {
        return $this->hydrator->hydrate($this->getIterator()->current());
    }

    /**
     * {@inheritDoc}
     */
    public function key(): mixed
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
     * @return Generator<int, mixed>
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
     * @return Generator<int, mixed>
     *
     * @phpstan-param iterable<int, mixed> $iterable
     */
    private function wrapIterable(iterable $iterable): Generator
    {
        foreach ($iterable as $key => $value) {
            yield $key => $value;
        }
    }
}
