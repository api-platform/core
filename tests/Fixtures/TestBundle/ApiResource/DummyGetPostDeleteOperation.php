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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/dummy_get_post_delete_operations/{id}',
        provider: [self::class, 'provideItem'],
    ),
    new GetCollection(
        uriTemplate: '/dummy_get_post_delete_operations',
        provider: [self::class, 'provide'], ),
    new Post(
        uriTemplate: '/dummy_get_post_delete_operations',
        provider: [self::class, 'provide'], ),
    new Delete(
        uriTemplate: '/dummy_get_post_delete_operations/{id}',
        provider: [self::class, 'provideItem'], ),
])]
class DummyGetPostDeleteOperation
{
    public ?int $id;

    public ?string $name = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $dummyResource = new self();
        $dummyResource->id = 1;
        $dummyResource->name = 'Dummy name';

        return [
            $dummyResource,
        ];
    }

    public static function provideItem(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $dummyResource = new self();
        $dummyResource->id = $uriVariables['id'];
        $dummyResource->name = 'Dummy name';

        return $dummyResource;
    }
}
