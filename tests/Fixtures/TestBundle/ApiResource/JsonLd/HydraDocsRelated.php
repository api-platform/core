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

#[ApiResource(
    shortName: 'JsonLdHydraDocsRelated',
    types: ['https://schema.org/Product'],
    operations: [
        new GetCollection(uriTemplate: '/jsonld_hydra_docs_related', provider: [self::class, 'provideCollection']),
        new Get(uriTemplate: '/jsonld_hydra_docs_related/{id}', uriVariables: ['id'], provider: [self::class, 'provide']),
    ],
)]
class HydraDocsRelated
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $name = '';

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);

        return $r;
    }

    public static function provideCollection(): array
    {
        return [];
    }
}
