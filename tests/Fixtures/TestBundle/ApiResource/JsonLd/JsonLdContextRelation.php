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
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    shortName: 'JsonLdContextRelation',
    operations: [
        new GetCollection(uriTemplate: '/jsonld_context_relations', provider: [self::class, 'provide']),
    ],
)]
class JsonLdContextRelation
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public ?string $name = null;

    public static function provide(): array
    {
        return [];
    }
}
