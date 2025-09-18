<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Metadata;

/**
 * An Operation dictionnary.
 *
 * @template-covariant T of Operation
 */
final class Operations implements \IteratorAggregate, \Countable
{
    /**
     * @var list<array{0: string, 1: T}>
     */
    private array $operations = [];

    /**
     * @param list<T>|array<string, T> $operations
     */
    public function __construct(array $operations = [])
    {
        foreach ($operations as $operationName => $operation) {
            // When we use an int-indexed array in the constructor, compute priorities
            if (\is_int($operationName) && null === $operation->getPriority()) {
                $operation = $operation->withPriority($operationName);
                $operationName = (string) $operationName;
            }

            if ($operation->getName()) {
                $operationName = $operation->getName();
            }

            $this->operations[] = [$operationName, $operation];
        }

        $this->sort();
    }

    /**
     * @return \Iterator<string, T>
     */
    public function getIterator(): \Traversable
    {
        return (function (): \Generator {
            foreach ($this->operations as [$operationName, $operation]) {
                yield $operationName => $operation;
            }
        })();
    }

    public function add(string $key, Operation $value): self
    {
        foreach ($this->operations as $i => [$operationName, $operation]) {
            if ($operationName === $key) {
                $this->operations[$i] = [$key, $value];

                return $this;
            }
        }

        $this->operations[] = [$key, $value];

        return $this;
    }

    public function remove(string $key): self
    {
        foreach ($this->operations as $i => [$operationName, $operation]) {
            if ($operationName === $key) {
                unset($this->operations[$i]);

                return $this;
            }
        }

        throw new \RuntimeException(\sprintf('Could not remove operation "%s".', $key));
    }

    public function has(string $key): bool
    {
        foreach ($this->operations as $i => [$operationName, $operation]) {
            if ($operationName === $key) {
                return true;
            }
        }

        return false;
    }

    public function count(): int
    {
        return \count($this->operations);
    }

    public function sort(): self
    {
        usort($this->operations, fn ($a, $b): int => $a[1]->getPriority() - $b[1]->getPriority());

        return $this;
    }
}
