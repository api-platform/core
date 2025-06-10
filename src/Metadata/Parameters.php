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

use ApiPlatform\Metadata\Exception\RuntimeException;

/**
 * A parameter dictionnary.
 *
 * @implements \IteratorAggregate<string, Parameter>
 */
final class Parameters implements \IteratorAggregate, \Countable
{
    /**
     * @var array<int, array{0: string, 1: Parameter}>
     */
    private array $parameters = [];

    /**
     * @param array<int|string, Parameter> $parameters
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
            if ($parameterName === $key && $value::class === $parameter::class) {
                $this->parameters[$i] = [$key, $value];

                return $this;
            }
        }

        $this->parameters[] = [$key, $value];

        return $this;
    }

    /**
     * @template T of Parameter
     *
     * @param class-string<T> $parameterClass
     */
    public function remove(string $key, string $parameterClass = QueryParameter::class): self
    {
        foreach ($this->parameters as $i => [$parameterName, $parameter]) {
            if ($parameterName === $key && $parameterClass === $parameter::class) {
                unset($this->parameters[$i]);

                return $this;
            }
        }

        throw new RuntimeException(\sprintf('Could not remove parameter "%s".', $key));
    }

    /**
     * @template T of Parameter
     *
     * @param class-string<T> $parameterClass
     *
     * @return T|null
     */
    public function get(string $key, string $parameterClass = QueryParameter::class): ?Parameter
    {
        foreach ($this->parameters as [$parameterName, $parameter]) {
            if ($parameterName === $key && $parameterClass === $parameter::class) {
                return $parameter;
            }
        }

        return null;
    }

    /**
     * @template T of Parameter
     *
     * @param class-string<T> $parameterClass
     */
    public function has(string $key, string $parameterClass = QueryParameter::class): bool
    {
        foreach ($this->parameters as [$parameterName, $parameter]) {
            if ($parameterName === $key && $parameterClass === $parameter::class) {
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
        usort($this->parameters, static fn (array $a, array $b): int => $b[1]->getPriority() - $a[1]->getPriority());

        return $this;
    }
}
