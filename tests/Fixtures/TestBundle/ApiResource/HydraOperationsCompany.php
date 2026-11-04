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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HydraOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;

#[GetCollection(
    uriTemplate: '/hydra_operations_companies',
    normalizationContext: ['hydra_operations' => true],
    provider: [self::class, 'provideCollection'],
)]
#[Post(uriTemplate: '/hydra_operations_companies', security: "is_granted('ROLE_ADMIN')", processor: [self::class, 'process'])]
#[Get(
    uriTemplate: '/hydra_operations_companies/{id}{._format}',
    provider: [self::class, 'provide'],
    hydraOperations: [
        new HydraOperation(method: 'DELETE'),
        new HydraOperation(name: 'archive_hydra_operations_company'),
    ],
)]
#[Delete(uriTemplate: '/hydra_operations_companies/{id}', security: "is_granted('ROLE_ADMIN')", hideHydraOperation: true, provider: [self::class, 'provide'], processor: [self::class, 'process'])]
#[Patch(uriTemplate: '/hydra_operations_companies/{id}/archive', name: 'archive_hydra_operations_company', security: "is_granted('ROLE_USER') and object.owner == user.getUserIdentifier()", provider: [self::class, 'provide'], processor: [self::class, 'process'])]
class HydraOperationsCompany
{
    public function __construct(
        #[ApiProperty(identifier: true)] public int $id,
        public string $owner,
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        return self::provideCollection()[(int) $uriVariables['id'] - 1];
    }

    /**
     * @return list<self>
     */
    public static function provideCollection(): array
    {
        return [new self(1, 'dunglas'), new self(2, 'admin')];
    }

    public static function process(mixed $data): mixed
    {
        return $data;
    }
}
