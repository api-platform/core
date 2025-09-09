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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource(types: 'https://schema.org/AggregateRating', operations: [])]
final class AggregateRating
{
    public function __construct(
        #[ApiProperty(iris: ['https://schema.org/ratingValue'])]
        public float $ratingValue,
        #[ApiProperty(iris: ['https://schema.org/reviewCount'])]
        public int $reviewCount,
    ) {
    }
}
