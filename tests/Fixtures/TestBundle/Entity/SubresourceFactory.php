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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\CreateProvider;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    types: ['https://schema.org/LocalBusiness'],
    operations: [
        new GetCollection(
            uriTemplate: '/subresource_organizations/{subresourceOrganizationId}/subresource_factories',
            uriVariables: [
                'subresourceOrganizationId' => new Link(toProperty: 'subresourceOrganization', fromClass: SubresourceOrganization::class),
            ]
        ),
        new Post(
            uriTemplate: '/subresource_organizations/{subresourceOrganizationId}/subresource_factories',
            uriVariables: [
                'subresourceOrganizationId' => new Link(toProperty: 'subresourceOrganization', fromClass: SubresourceOrganization::class),
            ],
            provider: CreateProvider::class
        ),
        new Get(
            uriTemplate: '/subresource_organizations/{subresourceOrganizationId}/subresource_factories/{id}',
            uriVariables: [
                'subresourceOrganizationId' => new Link(toProperty: 'subresourceOrganization', fromClass: SubresourceOrganization::class),
                'id' => new Link(fromClass: SubresourceFactory::class),
            ]
        ),
        new Put(
            uriTemplate: '/subresource_organizations/{subresourceOrganizationId}/subresource_factories/{id}',
            extraProperties: ['standard_put' => false],
            uriVariables: [
                'subresourceOrganizationId' => new Link(toProperty: 'subresourceOrganization', fromClass: SubresourceOrganization::class),
                'id' => new Link(fromClass: SubresourceFactory::class),
            ]
        ),
        new Delete(
            uriTemplate: '/subresource_organizations/{subresourceOrganizationId}/subresource_factories/{id}',
            uriVariables: [
                'subresourceOrganizationId' => new Link(toProperty: 'subresourceOrganization', fromClass: SubresourceOrganization::class),
                'id' => new Link(fromClass: SubresourceFactory::class),
            ]
        ),
    ]
)]
#[ORM\Entity]
class SubresourceFactory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'factories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SubresourceOrganization $subresourceOrganization = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSubresourceOrganization(): ?SubresourceOrganization
    {
        return $this->subresourceOrganization;
    }

    public function setSubresourceOrganization(?SubresourceOrganization $subresourceOrganization): self
    {
        $this->subresourceOrganization = $subresourceOrganization;

        return $this;
    }
}
