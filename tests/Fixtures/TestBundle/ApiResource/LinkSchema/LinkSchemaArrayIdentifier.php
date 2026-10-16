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
            uriTemplate: '/link_schema_array_identifiers/{id}',
            uriVariables: [
                'id' => new Link(
                    fromProperty: 'id',
                    fromClass: LinkSchemaArrayIdentifier::class,
                    identifiers: ['id']
                ),
            ],
            provider: [LinkSchemaArrayIdentifier::class, 'provide']
        ),
    ]
)]
class LinkSchemaArrayIdentifier
{
    /**
     * @var string[]
     */
    #[ApiProperty(identifier: true, schema: ['type' => 'array', 'items' => ['type' => 'string']])]
    public array $id = [];

    public static function provide(): self
    {
        return new self();
    }
}
