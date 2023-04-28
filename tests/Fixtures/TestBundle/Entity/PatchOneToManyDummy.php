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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [new Get(), new Patch(inputFormats: ['json' => ['application/merge-patch+json'], 'jsonapi'])],
)]
#[ORM\Entity]
class PatchOneToManyDummy
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ApiProperty(writableLink: true)]
    #[ORM\OneToMany(mappedBy: 'related', targetEntity: PatchOneToManyDummyRelationWithConstructor::class, cascade: ['all'])]
    protected Collection $relations;

    public function __construct()
    {
        $this->relations = new ArrayCollection();
    }

    public function addRelation(PatchOneToManyDummyRelationWithConstructor $relation): void
    {
        $this->relations->add($relation);
        $relation->setRelated($this);
    }

    public function removeRelation(PatchOneToManyDummyRelationWithConstructor $relation): void
    {
        $this->relations->removeElement($relation);
        $relation->setRelated(null);
    }

    /**
     * @return Collection<PatchOneToManyDummyRelationWithConstructor>
     */
    public function getRelations(): Collection
    {
        return $this->relations;
    }
}
