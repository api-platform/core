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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    shortName: 'JsonApiCrudDummy',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new GetCollection(
            uriTemplate: '/jsonapi_crud_dummies',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/jsonapi_crud_dummies/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/jsonapi_crud_dummies',
            processor: [self::class, 'process'],
        ),
        new Patch(
            uriTemplate: '/jsonapi_crud_dummies/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
            processor: [self::class, 'process'],
        ),
    ],
)]
class CrudDummy
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public string $name = '';

    public ?string $dummyDate = null;

    public ?CrudRelatedDummy $relatedDummy = null;

    /** @var CrudRelatedDummy[] */
    public array $relatedDummies = [];

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

    public static function process(self $data): self
    {
        if (0 === $data->id) {
            $data->id = 1;
        }

        return $data;
    }
}
