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
 * Related Owned Dummy.
 *
 * @author Sergey V. Ryabov <sryabov@mhds.ru>
 *
 * @ApiResource(iri="https://schema.org/Product")
 * @ORM\Entity
 */
class RelatedOwnedDummy
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
     * @ORM\OneToOne(targetEntity="Dummy", cascade={"persist"}, inversedBy="relatedOwnedDummy")
     * @ORM\JoinColumn(nullable=false)
     * @ApiSubresource
     */
    public $owningDummy;

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
     * Get owning dummy.
     *
     * @return Dummy
     */
    public function getOwningDummy()
    {
        return $this->owningDummy;
    }

    /**
     * Set owning dummy.
     *
     * @param Dummy $owningDummy the value to set
     */
    public function setOwningDummy(Dummy $owningDummy)
    {
        $this->owningDummy = $owningDummy;
    }
}
