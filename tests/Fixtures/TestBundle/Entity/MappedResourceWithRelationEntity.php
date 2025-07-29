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

use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceWithRelation;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ORM\Entity]
#[Map(target: MappedResourceWithRelation::class)]
class MappedResourceWithRelationEntity
{
    #[ORM\Id, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MappedResourceWithRelationRelatedEntity::class)]
    #[Map(target: 'relation')]
    #[Map(target: 'relationName', transform: [self::class, 'transformRelation'])]
    private ?MappedResourceWithRelationRelatedEntity $related = null;

    public static function transformRelation($value, $source)
    {
        return $source->getRelated()->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id = null)
    {
        $this->id = $id;

        return $this;
    }

    public function getRelated(): ?MappedResourceWithRelationRelatedEntity
    {
        return $this->related;
    }

    public function setRelated(?MappedResourceWithRelationRelatedEntity $related): self
    {
        $this->related = $related;

        return $this;
    }
}
