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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\UnionIriCollection;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[Get(
    shortName: 'UnionIriCollectionBar',
    uriTemplate: '/union_iri_collection_bars/{id}',
    provider: [self::class, 'provide'],
)]
class Bar
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $bar = new self();
        $bar->id = (int) ($uriVariables['id'] ?? 1);

        return $bar;
    }
}
