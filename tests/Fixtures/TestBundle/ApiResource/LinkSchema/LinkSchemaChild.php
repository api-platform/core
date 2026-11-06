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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\LinkSchema;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/link_schema_parents/{parentId}/children/{id}',
            uriVariables: [
                'parentId' => new Link(
                    fromProperty: 'id',
                    fromClass: LinkSchemaParent::class
                ),
                'id' => new Link(
                    fromClass: LinkSchemaChild::class,
                    identifiers: ['id']
                ),
            ],
            provider: [LinkSchemaChild::class, 'provide']
        ),
        new Get(
            uriTemplate: '/link_schema_parents_by_code/{parentCode}/children/{id}',
            uriVariables: [
                'parentCode' => new Link(
                    fromProperty: 'code',
                    fromClass: LinkSchemaParent::class
                ),
                'id' => new Link(
                    fromClass: LinkSchemaChild::class,
                    identifiers: ['id']
                ),
            ],
            provider: [LinkSchemaChild::class, 'provide']
        ),
    ]
)]
class LinkSchemaChild
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public ?string $title = null;

    public static function provide(): self
    {
        return new self();
    }
}
