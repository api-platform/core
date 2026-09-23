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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GrouplessSiblingOperation',
    operations: [
        new Get(uriTemplate: '/groupless_sibling_operations/{id}', uriVariables: ['id'], normalizationContext: ['groups' => ['groupless_sibling_operation:item']], provider: [self::class, 'provide']),
        // Declares no groups at all: every property is serialized, regardless of #[Groups].
        new GetCollection(uriTemplate: '/groupless_sibling_operations', provider: [self::class, 'provideCollection']),
    ],
)]
class GrouplessSiblingOperationResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['groupless_sibling_operation:item'])]
    public ?int $id = null;

    #[Groups(['groupless_sibling_operation:item'])]
    public ?string $inItemGroup = null;

    // No #[Groups]: only the groupless collection operation serializes it.
    public ?string $onlyInGrouplessOperation = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);
        $r->inItemGroup = 'item-1';
        $r->onlyInGrouplessOperation = 'groupless-1';

        return $r;
    }

    public static function provideCollection(): array
    {
        return [self::provide(new Get(), ['id' => 1])];
    }
}
