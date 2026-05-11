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
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\UrlGeneratorInterface;

#[ApiResource(
    shortName: 'JsonLdAbsoluteUrlChild',
    urlGenerationStrategy: UrlGeneratorInterface::ABS_URL,
    operations: [
        new GetCollection(
            uriTemplate: '/jsonld_absolute_url_children',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/jsonld_absolute_url_children/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/jsonld_absolute_url_children',
            processor: [self::class, 'process'],
        ),
        new GetCollection(
            uriTemplate: '/jsonld_absolute_url_parents/{parentId}/children',
            uriVariables: [
                'parentId' => new Link(fromClass: AbsoluteUrlParent::class, identifiers: ['id']),
            ],
            provider: [self::class, 'provideCollection'],
        ),
    ],
)]
class AbsoluteUrlChild
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public ?AbsoluteUrlParent $parent = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);
        $r->parent = AbsoluteUrlParent::provide($operation, ['id' => 1], $context);

        return $r;
    }

    public static function provideCollection(): array
    {
        return [self::provide(new Get(), ['id' => 1], [])];
    }

    public static function process(self $data): self
    {
        $data->id = 2;

        return $data;
    }
}
