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
    shortName: 'JsonApiClientGeneratedId',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new Get(
            uriTemplate: '/jsonapi_client_generated_ids/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/jsonapi_client_generated_ids_opt_in',
            denormalizationContext: [ItemNormalizer::ALLOW_CLIENT_GENERATED_ID => true],
            processor: [self::class, 'process'],
        ),
        new Post(
            uriTemplate: '/jsonapi_client_generated_ids',
            processor: [self::class, 'process'],
        ),
    ],
)]
class ClientGeneratedId
{
    #[ApiProperty(identifier: true)]
    public ?string $id = null;

    public ?string $name = null;

    public static function provide(): self
    {
        $resource = new self();
        $resource->id = '1';
        $resource->name = 'existing';

        return $resource;
    }

    public static function process(self $data): self
    {
        $data->id ??= 'server-generated';

        return $data;
    }
}
