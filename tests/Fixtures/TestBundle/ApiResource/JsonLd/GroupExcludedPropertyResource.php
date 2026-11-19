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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GroupExcludedProperty',
    normalizationContext: ['groups' => ['group_excluded_property:read']],
    denormalizationContext: ['groups' => ['group_excluded_property:write']],
    operations: [
        new Get(uriTemplate: '/group_excluded_properties/{id}', uriVariables: ['id'], provider: [self::class, 'provide']),
    ],
)]
class GroupExcludedPropertyResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['group_excluded_property:read'])]
    public ?int $id = null;

    #[Groups(['group_excluded_property:read', 'group_excluded_property:write'])]
    public ?string $exposed = null;

    public ?string $notInAnyGroup = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);

        return $r;
    }
}
