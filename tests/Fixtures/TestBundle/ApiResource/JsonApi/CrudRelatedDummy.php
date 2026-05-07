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
    shortName: 'JsonApiCrudRelatedDummy',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new GetCollection(
            uriTemplate: '/jsonapi_crud_related_dummies',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/jsonapi_crud_related_dummies/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/jsonapi_crud_related_dummies',
            processor: [self::class, 'processCreate'],
        ),
        new Patch(
            uriTemplate: '/jsonapi_crud_related_dummies/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
            processor: [self::class, 'processUpdate'],
        ),
    ],
)]
class CrudRelatedDummy
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public string $name = 'John Doe';

    public ?int $age = 23;

    public ?CrudThirdLevel $thirdLevel = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);
        $r->thirdLevel = CrudThirdLevel::provide($operation, ['id' => 1], $context);

        return $r;
    }

    public static function provideCollection(): array
    {
        return [self::provide(new Get(), ['id' => 1], [])];
    }

    public static function processCreate(self $data): self
    {
        // Mimic behat: a related dummy already exists with id=1, new POST gets id=2.
        $data->id = 2;

        return $data;
    }

    public static function processUpdate(self $data): self
    {
        return $data;
    }
}
