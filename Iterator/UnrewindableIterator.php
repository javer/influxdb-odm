<?php

namespace Javer\InfluxDB\ODM\Iterator;

use Generator;
use LogicException;
use RuntimeException;

class UnrewindableIterator implements IteratorInterface
{
    /**
     * @phpstan-var Generator<int, mixed>|null
     */
    private ?Generator $iterator;

    private bool $iteratorAdvanced = false;

    /**
     * UnrewindableIterator constructor.
     *
     * @param iterable $iterable
     *
     * @phpstan-param iterable<int, mixed> $iterable
     */
    public function __construct(iterable $iterable)
    {
        $this->iterator = $this->wrapIterable($iterable);
        $this->iterator->key();
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $this->preventRewinding(__METHOD__);

        $toArray = function () {
            if (!$this->valid()) {
                return;
            }

            yield $this->key() => $this->current();

            yield from $this->getIterator();
        };

        return iterator_to_array($toArray());
    }

    /**
     * {@inheritDoc}
     */
    public function current(): mixed
    {
        return $this->getIterator()->current();
    }

    /**
     * {@inheritDoc}
     */
    public function key(): mixed
    {
        if ($this->iterator) {
            return $this->iterator->key();
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        if (!$this->iterator) {
            return;
        }

        $this->iterator->next();
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        $this->preventRewinding(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function valid(): bool
    {
        return $this->key() !== null;
    }

    /**
     * Prevent rewinding.
     *
     * @param string $method
     *
     * @throws LogicException
     */
    private function preventRewinding(string $method): void
    {
        if ($this->iteratorAdvanced) {
            throw new LogicException(sprintf(
                'Cannot call %s for iterator that already yielded results',
                $method
            ));
        }
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

            $this->iteratorAdvanced = true;
        }

        $this->iterator = null;
    }
}
