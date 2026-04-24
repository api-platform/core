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

use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    shortName: 'ResourceWithNonResourceRelation',
    operations: [
        new GetCollection(
            uriTemplate: '/resources_with_non_resource_relations',
            parameters: [
                'name' => new QueryParameter(
                    filter: new PartialSearchFilter(),
                    property: 'nonResourceRelation.name',
                ),
                'category' => new QueryParameter(
                    filter: new PartialSearchFilter(),
                    property: 'nonResourceRelation.category',
                ),
            ],
        ),
    ]
)]
#[ORM\Entity]
class ResourceWithNonResourceRelation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\ManyToOne(targetEntity: NonResourceRelation::class)]
    private ?NonResourceRelation $nonResourceRelation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getNonResourceRelation(): ?NonResourceRelation
    {
        return $this->nonResourceRelation;
    }

    public function setNonResourceRelation(?NonResourceRelation $nonResourceRelation): self
    {
        $this->nonResourceRelation = $nonResourceRelation;

        return $this;
    }
}
