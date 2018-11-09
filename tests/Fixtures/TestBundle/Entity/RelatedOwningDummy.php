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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Related Owning Dummy.
 *
 * @author Sergey V. Ryabov <sryabov@mhds.ru>
 *
 * @ApiResource(iri="https://schema.org/Product")
 * @ORM\Entity
 */
class RelatedOwningDummy
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string A name
     *
     * @ORM\Column(nullable=true)
     */
    public $name;

    /**
     * @var Dummy
     *
     * @ORM\OneToOne(targetEntity="Dummy", cascade={"persist"}, mappedBy="relatedOwningDummy")
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
