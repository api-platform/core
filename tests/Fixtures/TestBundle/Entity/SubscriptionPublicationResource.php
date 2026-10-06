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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ApiResource(
    operations: [new Get(mercure: "object.restEnabled ? {'private': false, 'hub': 'rest', 'enable_async_update': false} : false")],
    graphQlOperations: [
        new Query(),
        new Subscription(name: 'item', shortName: 'ScopedPublication', normalizationContext: ['groups' => ['subscription'], 'skip_null_values' => true]),
        new SubscriptionCollection(name: 'watch', shortName: 'ScopedPublication', normalizationContext: ['groups' => ['subscription'], 'skip_null_values' => true]),
        new SubscriptionCollection(name: 'conditional', shortName: 'ScopedPublication', normalizationContext: ['groups' => ['subscription'], 'skip_null_values' => true], mercure: "object.name == 'Muted' ? false : {'private': true, 'private_fields': ['tenant'], 'hub': 'graphql', 'enable_async_update': false}"),
        new SubscriptionCollection(name: 'disabled', mercure: false),
    ],
    mercure: ['private' => true, 'private_fields' => ['tenant'], 'hub' => 'graphql', 'enable_async_update' => false],
)]
#[ApiResource(
    uriTemplate: '/publication_aliases/{id}',
    operations: [new Get(mercure: "object.restEnabled ? {'private': false, 'hub': 'rest', 'enable_async_update': false} : false")],
    graphQlOperations: [new Query(name: 'alias')],
)]
class SubscriptionPublicationResource
{
    #[ORM\Id, ORM\Column, Groups(['subscription'])]
    public int $id;
    #[ORM\Column(nullable: true), Groups(['subscription'])]
    public ?string $name = 'Initial';
    #[ORM\Column]
    public string $tenant = 'tenant-a';
    #[ORM\Column]
    public bool $restEnabled = false;
}
