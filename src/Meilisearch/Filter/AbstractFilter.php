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

namespace ApiPlatform\Meilisearch\Filter;

/**
 * @author API Platform Community
 */
abstract class AbstractFilter implements FilterInterface
{
    /**
     * @param string[]|null $properties null means "no restriction" (every filterable-in-the-index property is accepted)
     */
    public function __construct(protected readonly ?array $properties = null)
    {
    }

    /**
     * @return string[]
     */
    protected function getProperties(string $resourceClass): array
    {
        return $this->properties ?? [];
    }

    protected function isPropertyEnabled(string $property): bool
    {
        return null === $this->properties || \in_array($property, $this->properties, true);
    }

    /**
     * Quotes a value for use inside a Meilisearch filter expression.
     */
    protected function quote(mixed $value): string
    {
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return '"'.str_replace('"', '\\"', (string) $value).'"';
    }
}
