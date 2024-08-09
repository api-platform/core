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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;

/**
 * Filter attribute.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class ApiFilter
{
    /**
     * @param string|class-string<FilterInterface> $filterClass
     * @param string                               $alias       a filter tag alias to be referenced in a Parameter
     */
    public function __construct(
        public string $filterClass,
        public ?string $id = null,
        public ?string $strategy = null,
        public array $properties = [],
        public array $arguments = [],
        public ?string $alias = null,
    ) {
        if (!is_a($this->filterClass, FilterInterface::class, true)) {
            throw new InvalidArgumentException(\sprintf('The filter class "%s" does not implement "%s". Did you forget a use statement?', $this->filterClass, FilterInterface::class));
        }
    }
}
