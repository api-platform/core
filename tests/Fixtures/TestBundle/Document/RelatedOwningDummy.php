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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Related Owning Dummy.
 *
 * @author Sergey V. Ryabov <sryabov@mhds.ru>
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @ApiResource(iri="https://schema.org/Product")
 * @ODM\Document
 */
class RelatedOwningDummy
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @var string A name
     *
     * @ODM\Field(type="string")
     */
    public $name;

    /**
     * @var Dummy
     *
     * @ODM\ReferenceOne(targetDocument=Dummy::class, cascade={"persist"}, mappedBy="relatedOwningDummy")
     * @ApiSubresource
     */
    public $ownedDummy;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Get owned dummy.
     *
     * @return Dummy
     */
    public function getOwnedDummy()
    {
        return $this->ownedDummy;
    }

    /**
     * Set owned dummy.
     *
     * @param Dummy $ownedDummy the value to set
     */
    public function setOwnedDummy(Dummy $ownedDummy)
    {
        $this->ownedDummy = $ownedDummy;

        if ($this !== $this->ownedDummy->getRelatedOwningDummy()) {
            $this->ownedDummy->setRelatedOwningDummy($this);
        }
    }
}
