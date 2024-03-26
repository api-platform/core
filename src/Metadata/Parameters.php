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
 * A parameter dictionnary.
 *
 * @implements \IteratorAggregate<string, Parameter>
 */
final class Parameters implements \IteratorAggregate, \Countable
{
    private array $parameters = [];

    /**
     * @param array<string, Parameter> $parameters
     */
    public function __construct(array $parameters = [])
    {
        foreach ($parameters as $parameterName => $parameter) {
            if ($parameter->getKey()) {
                $parameterName = $parameter->getKey();
            }

            $this->parameters[] = [$parameterName, $parameter];
        }

        $this->sort();
    }

    /**
     * @return \ArrayIterator<string, Parameter>
     */
    public function getIterator(): \Traversable
    {
        return (function (): \Generator {
            foreach ($this->parameters as [$parameterName, $parameter]) {
                yield $parameterName => $parameter;
            }
        })();
    }

    public function add(string $key, Parameter $value): self
    {
        foreach ($this->parameters as $i => [$parameterName, $parameter]) {
            if ($parameterName === $key) {
                $this->parameters[$i] = [$key, $value];

                return $this;
            }
        }

        $this->parameters[] = [$key, $value];

        return $this;
    }

    public function get(string $key): ?Parameter
    {
        foreach ($this->parameters as $i => [$parameterName, $parameter]) {
            if ($parameterName === $key) {
                return $parameter;
            }
        }

        return null;
    }

    public function remove(string $key): self
    {
        foreach ($this->parameters as $i => [$parameterName, $parameter]) {
            if ($parameterName === $key) {
                unset($this->parameters[$i]);

                return $this;
            }
        }

        throw new \RuntimeException(sprintf('Could not remove parameter "%s".', $key));
    }

    public function has(string $key): bool
    {
        foreach ($this->parameters as $i => [$parameterName, $parameter]) {
            if ($parameterName === $key) {
                return true;
            }
        }

        return false;
    }

    public function count(): int
    {
        return \count($this->parameters);
    }

    public function sort(): self
    {
        usort($this->parameters, fn ($a, $b): int|float => $b[1]->getPriority() - $a[1]->getPriority());

        return $this;
    }
}
