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

final readonly class Definition
{
    /**
     * @param array<string, Attribute> $attributes
     */
    public function __construct(
        public string $name,
        public string $label = '',
        public ?string $security = null,
        public array $attributes = [],
    ) {
    }
}
