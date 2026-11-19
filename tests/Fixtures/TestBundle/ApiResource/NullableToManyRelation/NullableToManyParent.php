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
use Doctrine\Common\Collections\Collection;

#[Get(
    shortName: 'NullableToManyParent',
    uriTemplate: '/nullable_to_many_parents/{id}',
    provider: [self::class, 'provide'],
)]
class NullableToManyParent
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $name = '';

    /** @var Collection<int, NullableToManyChild>|null */
    #[ApiProperty(readableLink: true)]
    public ?Collection $children = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $p = new self();
        $p->id = (int) ($uriVariables['id'] ?? 1);
        $p->name = 'parent';
        $p->children = null;

        return $p;
    }
}
