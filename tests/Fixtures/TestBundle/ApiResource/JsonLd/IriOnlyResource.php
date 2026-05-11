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
    shortName: 'JsonLdIriOnlyResource',
    normalizationContext: ['iri_only' => true, 'jsonld_embed_context' => true],
    operations: [
        new GetCollection(
            uriTemplate: '/jsonld_iri_only_resources',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/jsonld_iri_only_resources/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
class IriOnlyResource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $foo = '';

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);
        $r->foo = "foo {$r->id}";

        return $r;
    }

    public static function provideCollection(): array
    {
        return array_map(static function (int $i): self {
            $r = new self();
            $r->id = $i;
            $r->foo = "foo {$i}";

            return $r;
        }, [1, 2, 3]);
    }
}
