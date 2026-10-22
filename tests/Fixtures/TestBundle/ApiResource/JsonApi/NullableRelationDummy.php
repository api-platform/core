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
use ApiPlatform\Metadata\Get;

#[ApiResource(
    shortName: 'JsonApiNullableRelation',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new Get(uriTemplate: '/jsonapi_nullable_relations/{id}', provider: [self::class, 'provide']),
    ],
)]
class NullableRelationDummy
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public ?NullableRelationDummy $parent = null;

    public static function provide(): self
    {
        return new self();
    }
}
