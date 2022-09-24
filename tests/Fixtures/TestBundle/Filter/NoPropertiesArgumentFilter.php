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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Filter;

use ApiPlatform\Api\FilterInterface;

final class NoPropertiesArgumentFilter implements FilterInterface
{
    public function __construct(
        private readonly string $foo = 'bar',
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        return [
            'property' => $this->foo,
            'type' => '',
            'required' => false,
        ];
    }
}
