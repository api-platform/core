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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\PHPCR;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * Related Owned Dummy.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @ApiResource(iri="https://schema.org/Product")
 * @PHPCRODM\Document(referenceable=true)
 */
class RelatedOwnedDummy
{
    /**
     * @PHPCRODM\Id
     */
    private $id;

    /**
     * @PHPCRODM\Node()
     */
    public $node;

    /**
     * @PHPCRODM\ParentDocument()
     */
    public $parentDocument;

    /**
     * @var string A name
     *
     * @PHPCRODM\Field(type="string")
     */
    public $name;

    /**
     * @var Dummy
     *
     * @PHPCRODM\ReferenceOne(targetDocument="Dummy", cascade={"persist"})
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
