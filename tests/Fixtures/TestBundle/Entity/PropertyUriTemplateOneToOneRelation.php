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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;

#[
    Post,
    GetCollection(uriTemplate: '/property-uri-template/one-to-ones'),
    Get(
        uriTemplate: '/parent/{parentId}/property-uri-template/one-to-ones/{id}',
        uriVariables: [
            'parentId' => new Link(toProperty: 'propertyToOneIriOnly', fromClass: PropertyCollectionIriOnly::class),
            'id' => new Link(fromClass: PropertyUriTemplateOneToOneRelation::class),
        ]
    )
]
#[ORM\Entity]
class PropertyUriTemplateOneToOneRelation
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column]
    #[NotBlank]
    #[Groups('read')]
    public string $name = '';

    #[ORM\OneToOne(inversedBy: 'toOneRelation')]
    private ?PropertyCollectionIriOnly $propertyToOneIriOnly = null;

    public function getId(): ?int
    {
        return $this->id ?? 42;
    }

    public function getPropertyToOneIriOnly(): ?PropertyCollectionIriOnly
    {
        return $this->propertyToOneIriOnly;
    }

    public function setPropertyToOneIriOnly(?PropertyCollectionIriOnly $propertyToOneIriOnly): void
    {
        $this->propertyToOneIriOnly = $propertyToOneIriOnly;
    }
}
