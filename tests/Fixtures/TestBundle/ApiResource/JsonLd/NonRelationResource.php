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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    shortName: 'JsonLdNonRelationResource',
    operations: [
        new Get(
            uriTemplate: '/jsonld_non_relation_resources/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/jsonld_non_relation_resources',
            processor: [self::class, 'process'],
        ),
    ],
)]
class NonRelationResource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public ?NonRelationPayload $relation = null;

    public static function provide(): self
    {
        return new self();
    }

    public static function process(self $data): self
    {
        $data->id = 1;

        return $data;
    }
}

final class NonRelationPayload
{
    public string $foo = '';
}
