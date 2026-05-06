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
    shortName: 'JsonLdHydraDocsDeprecated',
    deprecationReason: 'This resource is deprecated.',
    operations: [
        new GetCollection(uriTemplate: '/jsonld_hydra_docs_deprecated', provider: [self::class, 'provideCollection']),
        new Get(uriTemplate: '/jsonld_hydra_docs_deprecated/{id}', uriVariables: ['id'], provider: [self::class, 'provide']),
    ],
)]
class HydraDocsDeprecated
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    #[ApiProperty(deprecationReason: 'This field is deprecated.')]
    public ?string $deprecatedField = null;

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
