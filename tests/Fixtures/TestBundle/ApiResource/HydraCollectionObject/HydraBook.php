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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\HydraCollectionObject;

use ApiPlatform\Hydra\Collection;
use ApiPlatform\Hydra\PartialCollectionView;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/hydra_collection_objects', provider: [self::class, 'provide']),
        new Get(uriTemplate: '/hydra_collection_objects/{id}', uriVariables: ['id']),
    ]
)]
class HydraBook
{
    public function __construct(
        public string $id = '',
        public string $title = '',
    ) {
    }

    /**
     * @return Collection<HydraBook>
     */
    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): Collection
    {
        $collection = new Collection();
        $collection->member = [
            new self(id: '1', title: 'Hyperion'),
            new self(id: '2', title: 'Endymion'),
        ];
        $collection->totalItems = 2;
        $collection->view = new PartialCollectionView(
            '/hydra_collection_objects?page=1',
            first: '/hydra_collection_objects?page=1',
            last: '/hydra_collection_objects?page=1',
        );

        return $collection;
    }
}
