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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi;

use ApiPlatform\JsonApi\Serializer\ItemNormalizer;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    shortName: 'JsonApiStrictType',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new Get(uriTemplate: '/jsonapi_strict_types/{id}', provider: [self::class, 'provide']),
        new Post(
            uriTemplate: '/jsonapi_strict_types',
            denormalizationContext: [ItemNormalizer::STRICT_RESOURCE_TYPE => true],
            processor: [self::class, 'process'],
        ),
        new Post(
            uriTemplate: '/jsonapi_lenient_types',
            processor: [self::class, 'process'],
        ),
    ],
)]
#[ApiResource(
    shortName: 'JsonApiStrictTypeAlias',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new Post(
            uriTemplate: '/jsonapi_strict_type_aliases',
            denormalizationContext: [ItemNormalizer::STRICT_RESOURCE_TYPE => true],
            processor: [self::class, 'process'],
        ),
    ],
)]
class StrictTypeResource
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public ?string $name = null;

    public static function provide(): self
    {
        return new self();
    }

    public static function process(self $data): self
    {
        return $data;
    }
}
