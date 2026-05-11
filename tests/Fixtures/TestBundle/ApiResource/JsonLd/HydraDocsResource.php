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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A docs sample.
 */
#[ApiResource(
    shortName: 'JsonLdHydraDocs',
    operations: [
        new Get(uriTemplate: '/jsonld_hydra_docs/{id}', uriVariables: ['id'], provider: [self::class, 'provide']),
        new GetCollection(uriTemplate: '/jsonld_hydra_docs', provider: [self::class, 'provideCollection']),
        new Put(uriTemplate: '/jsonld_hydra_docs/{id}', uriVariables: ['id'], processor: [self::class, 'process']),
        new Delete(uriTemplate: '/jsonld_hydra_docs/{id}', uriVariables: ['id'], processor: [self::class, 'process']),
    ],
)]
class HydraDocsResource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    /**
     * The doc resource name.
     */
    #[ApiProperty(iris: ['https://schema.org/name'])]
    #[Assert\NotBlank]
    public string $name = '';

    public ?HydraDocsRelated $related = null;

    /** @var array<HydraDocsRelated> */
    public array $relateds = [];

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

    public static function process(mixed $data): mixed
    {
        return $data;
    }
}
