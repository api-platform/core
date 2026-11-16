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

            // `:property` is a template expanded per-property later; multiple templates with disjoint properties must coexist.
            if (str_contains((string) $parameterName, ':property')) {
                $key = \sprintf('%s.%s.%s', $parameter::class, $parameterName, self::propertyDiscriminator($parameter));
            } else {
                $key = \sprintf('%s.%s', $parameter::class, $parameterName);
            }

            $this->parameters[$key] = [$parameterName, $parameter];
        }

        $this->parameters = array_values($this->parameters);

        $this->sortInPlace();
    }

    public function __clone()
    {
        foreach ($this->parameters as $i => [$parameterName, $parameter]) {
            $this->parameters[$i] = [$parameterName, clone $parameter];
        }
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

    /**
     * @deprecated since API Platform 5.1, use {@see Parameters::with()} instead
     */
    public function add(string $key, Parameter $value): self
    {
        trigger_deprecation('api-platform/core', '5.1', 'Method "%s::add()" is deprecated and will be removed in 6.0, use "with()" which returns a new instance.', self::class);

        return $this->addInPlace($key, $value);
    }

    private function addInPlace(string $key, Parameter $value): self
    {
        // `:property` is a template expanded per-property later; templates with disjoint properties coexist, identical ones override.
        $isTemplate = str_contains($key, ':property');
        $valueDiscriminator = $isTemplate ? self::propertyDiscriminator($value) : null;

        foreach ($this->parameters as $i => [$parameterName, $parameter]) {
            if ($parameterName !== $key || $value::class !== $parameter::class) {
                continue;
            }

            if ($isTemplate && self::propertyDiscriminator($parameter) !== $valueDiscriminator) {
                continue;
            }

            $this->parameters[$i] = [$key, $value];

            return $this;
        }

        $this->parameters[] = [$key, $value];

        return $this;
    }

    public function with(string $key, Parameter $value): static
    {
        $self = new self();
        $self->parameters = $this->parameters;

        return $self->addInPlace($key, $value);
    }

    public function without(string $key, string $parameterClass = QueryParameter::class): static
    {
        if (null === $i = $this->indexOf($key, $parameterClass)) {
            throw new RuntimeException(\sprintf('Could not remove parameter "%s".', $key));
        }

        $self = new self();
        $self->parameters = $this->parameters;
        unset($self->parameters[$i]);
        $self->parameters = array_values($self->parameters);

        return $self;
    }

    public function sorted(): static
    {
        $self = new self();
        $self->parameters = $this->parameters;

        return $self->sortInPlace();
    }

    private static function propertyDiscriminator(Parameter $parameter): string
    {
        if ($properties = $parameter->getProperties()) {
            return '['.implode(',', $properties).']';
        }

        return $parameter->getProperty() ?? '';
    }

    /**
     * @template T of Parameter
     *
     * @param class-string<T> $parameterClass
     *
     * @deprecated since API Platform 5.1, use {@see Parameters::without()} instead
     */
    public function remove(string $key, string $parameterClass = QueryParameter::class): self
    {
        trigger_deprecation('api-platform/core', '5.1', 'Method "%s::remove()" is deprecated and will be removed in 6.0, use "without()" which returns a new instance.', self::class);

        if (null === $i = $this->indexOf($key, $parameterClass)) {
            throw new RuntimeException(\sprintf('Could not remove parameter "%s".', $key));
        }

        unset($this->parameters[$i]);

        return $this;
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
        if (null === $i = $this->indexOf($key, $parameterClass)) {
            return null;
        }

        return $this->parameters[$i][1];
    }

    /**
     * @template T of Parameter
     *
     * @param class-string<T> $parameterClass
     */
    public function has(string $key, string $parameterClass = QueryParameter::class): bool
    {
        return null !== $this->indexOf($key, $parameterClass);
    }

    private function indexOf(string $key, string $parameterClass): ?int
    {
        $subclassMatch = null;
        foreach ($this->parameters as $i => [$parameterName, $parameter]) {
            if ($parameterName !== $key) {
                continue;
            }

            if ($parameterClass === $parameter::class) {
                return $i;
            }

            if (null === $subclassMatch && $parameter instanceof $parameterClass) {
                $subclassMatch = $i;
            }
        }

        return $subclassMatch;
    }

    public function count(): int
    {
        return \count($this->parameters);
    }

    /**
     * @deprecated since API Platform 5.1, use {@see Parameters::sorted()} instead
     */
    public function sort(): self
    {
        trigger_deprecation('api-platform/core', '5.1', 'Method "%s::sort()" is deprecated and will be removed in 6.0, use "sorted()" which returns a new instance.', self::class);

        return $this->sortInPlace();
    }

    private function sortInPlace(): self
    {
        usort($this->parameters, static fn (array $a, array $b): int => $b[1]->getPriority() - $a[1]->getPriority());

        return $this;
    }
}
