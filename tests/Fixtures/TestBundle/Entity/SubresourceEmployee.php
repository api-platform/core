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
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    uriTemplate: '/subresource_organizations/{subresourceOrganization}/subresource_employees',
    types: ['https://schema.org/Person']
)]
#[ORM\Entity]
class SubresourceEmployee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'employees')]
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
