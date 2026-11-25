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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGenIdChain;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/json_schema_gen_id_chain/middles/{id}',
        normalizationContext: ['groups' => ['chain:read']],
        provider: [self::class, 'provide'],
    ),
])]
class GenIdChainMiddle
{
    #[ApiProperty(identifier: true)]
    #[Groups(['chain:read'])]
    public ?int $id = null;

    #[ApiProperty(genId: true)]
    #[Groups(['chain:read'])]
    public ?GenIdChainLeaf $second = null;

    public static function provide(): ?self
    {
        return null;
    }
}
