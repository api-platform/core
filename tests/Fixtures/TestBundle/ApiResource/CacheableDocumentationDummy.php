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
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    shortName: 'CacheableDocumentationDummy',
    operations: [
        new GetCollection(
            uriTemplate: '/cacheable_documentation_dummies',
            provider: [self::class, 'provide'],
        ),
    ],
)]
class CacheableDocumentationDummy
{
    public function __construct(#[ApiProperty(identifier: true)] public int $id)
    {
    }

    public static function provide(): iterable
    {
        return [new self(1), new self(2)];
    }
}
