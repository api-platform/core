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

namespace ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entity with two ApiResource declarations and mercure enabled on both.
 * Tests that Mercure publishes updates for each resource, not just the first.
 */
#[ApiResource(
    shortName: 'AdminDummyMercure',
    uriTemplate: '/admin/dummy_mercures/{id}{._format}',
    operations: [new Get(), new GetCollection(), new Post()],
    mercure: ['enable_async_update' => false, 'hub' => 'managed'],
    normalizationContext: ['groups' => ['admin:read']],
)]
#[ApiResource(
    shortName: 'DummyMercure',
    uriTemplate: '/dummy_mercures/{id}{._format}',
    operations: [new Get(), new GetCollection()],
    mercure: ['enable_async_update' => false, 'hub' => 'managed'],
    normalizationContext: ['groups' => ['read']],
)]
#[ORM\Entity]
class DummyMercureMultiResource
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column]
    public string $name = '';
}
