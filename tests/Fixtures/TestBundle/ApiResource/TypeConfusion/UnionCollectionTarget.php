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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\TypeConfusion;

use ApiPlatform\Metadata\Post;

#[Post(uriTemplate: '/type-confusion/union-collection-targets{._format}')]
class UnionCollectionTarget
{
    public ?string $name = null;

    /**
     * PHPDoc-only union array: each element may be either Foo or Bar.
     * Mirrors the real-world "@var PharmacyRevenueFile[]|MerchFile[]" pattern
     * that regressed under the GHSA-9rjg-x2p2-h68h security fix.
     *
     * @var Foo[]|Bar[]
     */
    public array $attachments = [];
}