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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource;

final readonly class Attribute
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $name,
        public AttributeType $type,
        public bool $required = false,
        public bool $collection = false,
        public array $options = [],
        public bool $filterable = false,
    ) {
    }

    public function target(): ?string
    {
        return AttributeType::Reference === $this->type ? (string) ($this->options['target'] ?? '') : null;
    }
}
