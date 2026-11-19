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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\UnionIriCollection;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Post;

#[Post(
    shortName: 'UnionIriCollectionContainer',
    uriTemplate: '/union_iri_collection_containers',
    processor: [self::class, 'process'],
)]
class Container
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    /**
     * @var array<array-key, Foo|Bar>
     */
    public array $attachments = [];

    public static function process(mixed $data): self
    {
        return $data;
    }
}
