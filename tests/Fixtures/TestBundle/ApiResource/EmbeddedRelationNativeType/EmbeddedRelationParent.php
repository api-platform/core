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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\EmbeddedRelationNativeType;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Post;

#[Post(
    shortName: 'EmbeddedRelationParent',
    uriTemplate: '/embedded_relation_parents',
    processor: [self::class, 'process'],
)]
class EmbeddedRelationParent
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    /**
     * @var EmbeddedRelationChild[]
     */
    #[ApiProperty(writableLink: true, readableLink: true)]
    public array $items = [];

    public static function process(mixed $data): self
    {
        return $data;
    }
}
