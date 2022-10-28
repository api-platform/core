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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ApiResource(
 *     itemOperations={
 *         "get",
 *         "patch"={"input_formats"={"json"={"application/merge-patch+json"}, "jsonapi"}}
 *     }
 * )
 * @ODM\Document
 */
class PatchOneToManyDummy
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    public $id;

    /**
     * @ApiProperty(writableLink=true)
     * @ODM\ReferenceMany(targetDocument=PatchOneToManyDummyRelationWithConstructor::class, mappedBy="related", cascade={"all"})
     */
    protected $relations;

    public function __construct()
    {
        $this->relations = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
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
