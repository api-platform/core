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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    shortName: 'JsonApiCrudRelationEmbedder',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new Post(
            uriTemplate: '/jsonapi_crud_relation_embedders',
            processor: [self::class, 'process'],
        ),
    ],
)]
class CrudRelationEmbedder
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public string $krondstadt = 'Krondstadt';

    public ?CrudRelatedDummy $related = null;

    public static function process(self $data): self
    {
        $data->id = 1;

        return $data;
    }
}
