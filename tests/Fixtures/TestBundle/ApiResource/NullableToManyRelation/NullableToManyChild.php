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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\NullableToManyRelation;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[Get(
    shortName: 'NullableToManyChild',
    uriTemplate: '/nullable_to_many_children/{id}',
    provider: [self::class, 'provide'],
)]
class NullableToManyChild
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $name = '';

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $c = new self();
        $c->id = (int) ($uriVariables['id'] ?? 1);
        $c->name = 'child';

        return $c;
    }
}
