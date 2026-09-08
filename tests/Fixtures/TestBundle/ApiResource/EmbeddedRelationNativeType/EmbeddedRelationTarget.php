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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[Get(
    shortName: 'EmbeddedRelationTarget',
    uriTemplate: '/embedded_relation_targets/{id}',
    provider: [self::class, 'provide'],
)]
class EmbeddedRelationTarget
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $target = new self();
        $target->id = (int) ($uriVariables['id'] ?? 1);

        return $target;
    }
}
