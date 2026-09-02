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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\HttpCachePurgeMultiCollection;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(
    shortName: 'HttpCachePurgeMultiCollectionDummy',
    operations: [
        new Post(uriTemplate: '/http_cache_purge_multi_collection_dummies'),
        new GetCollection(uriTemplate: '/http_cache_purge_multi_collection_dummies'),
        new GetCollection(uriTemplate: '/http_cache_purge_multi_collection_dummies/featured'),
        new Get(uriTemplate: '/http_cache_purge_multi_collection_dummies/{id}'),
        new Get(uriTemplate: '/http_cache_purge_multi_collection_dummies/{id}/details'),
        new Patch(uriTemplate: '/http_cache_purge_multi_collection_dummies/{id}'),
    ]
)]
class Dummy
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(type: 'string')]
    public string $name = '';

    public function getId(): ?int
    {
        return $this->id;
    }
}
