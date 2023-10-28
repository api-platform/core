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

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Related Owning Dummy.
 *
 * @author Sergey V. Ryabov <sryabov@mhds.ru>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
#[ApiResource(types: ['https://schema.org/Product'])]
#[ODM\Document]
class RelatedOwningDummy
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;
    /**
     * @var string A name
     */
    #[ODM\Field(type: 'string')]
    public $name;
    #[ODM\ReferenceOne(targetDocument: Dummy::class, cascade: ['persist'], mappedBy: 'relatedOwningDummy')]
    public ?Dummy $ownedDummy = null;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Get owned dummy.
     */
    public function getOwnedDummy(): Dummy
    {
        return $this->ownedDummy;
    }

    /**
     * Set owned dummy.
     *
     * @param Dummy $ownedDummy the value to set
     */
    public function setOwnedDummy(Dummy $ownedDummy): void
    {
        $this->ownedDummy = $ownedDummy;
        if ($this !== $this->ownedDummy->getRelatedOwningDummy()) {
            $this->ownedDummy->setRelatedOwningDummy($this);
        }
    }
}
