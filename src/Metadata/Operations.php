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

final class Operations implements \IteratorAggregate, \Countable
{
    private $operations;

    /**
     * @param array<string|int, Operation> $operations
     */
    public function __construct(array $operations = [])
    {
        $this->operations = [];
        foreach ($operations as $operationName => $operation) {
            // When we use an int-indexed array in the constructor, compute priorities
            if (\is_int($operationName)) {
                $operation = $operation->withPriority($operationName);
            }

            $this->operations[] = [$operationName, $operation];
        }

        $this->sort();
    }

    public function getIterator(): \Traversable
    {
        return (function () {
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

        throw new \RuntimeException(sprintf('Could not remove operation "%s".', $key));
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
        usort($this->operations, function ($a, $b) {
            return $a[1]->getPriority() - $b[1]->getPriority();
        });

        return $this;
    }
}
