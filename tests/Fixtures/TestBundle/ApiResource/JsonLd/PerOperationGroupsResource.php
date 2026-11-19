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
    shortName: 'PerOperationGroups',
    operations: [
        new Get(uriTemplate: '/per_operation_groups/{id}', uriVariables: ['id'], normalizationContext: ['groups' => ['per_operation_groups:item']], provider: [self::class, 'provide']),
        new GetCollection(uriTemplate: '/per_operation_groups', normalizationContext: ['groups' => ['per_operation_groups:collection']], provider: [self::class, 'provideCollection']),
    ],
)]
class PerOperationGroupsResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['per_operation_groups:item', 'per_operation_groups:collection'])]
    public ?int $id = null;

    #[Groups(['per_operation_groups:item'])]
    public ?string $detail = null;

    #[Groups(['per_operation_groups:collection'])]
    public ?string $summary = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);
        $r->detail = 'detail-1';
        $r->summary = 'summary-1';

        return $r;
    }

    public static function provideCollection(): array
    {
        $r = self::provide(new Get(), ['id' => 1]);

        return [$r];
    }
}
