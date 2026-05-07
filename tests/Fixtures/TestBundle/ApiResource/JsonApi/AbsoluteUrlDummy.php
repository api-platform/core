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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\UrlGeneratorInterface;

#[ApiResource(
    shortName: 'JsonApiAbsoluteUrlDummy',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    urlGenerationStrategy: UrlGeneratorInterface::ABS_URL,
    paginationItemsPerPage: 3,
    operations: [
        new GetCollection(
            uriTemplate: '/jsonapi_absolute_url_dummies',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/jsonapi_absolute_url_dummies/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new GetCollection(
            uriTemplate: '/jsonapi_absolute_url_relation_dummies/{relationId}/absolute_url_dummies',
            uriVariables: [
                'relationId' => new Link(fromClass: AbsoluteUrlRelationDummy::class, identifiers: ['id']),
            ],
            provider: [self::class, 'provideCollection'],
        ),
    ],
)]
class AbsoluteUrlDummy
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public ?AbsoluteUrlRelationDummy $absoluteUrlRelationDummy = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);
        $r->absoluteUrlRelationDummy = AbsoluteUrlRelationDummy::provide($operation, ['id' => 1], $context);

        return $r;
    }

    public static function provideCollection(): array
    {
        return [self::provide(new Get(), ['id' => 1], [])];
    }
}
