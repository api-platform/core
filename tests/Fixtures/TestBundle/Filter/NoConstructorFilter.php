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

final class NoConstructorFilter implements FilterInterface
{
    public function getDescription(string $resourceClass): array
    {
        return [
            'property' => 'foo',
            'type' => '',
            'required' => false,
        ];
    }
}
