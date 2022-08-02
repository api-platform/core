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
 * Related Owned Dummy.
 *
 * @author Sergey V. Ryabov <sryabov@mhds.ru>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
#[ApiResource(types: ['https://schema.org/Product'])]
#[ODM\Document]
class RelatedOwnedDummy
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;
    /**
     * @var string|null A name
     */
    #[ODM\Field(type: 'string')]
    public $name;
    #[ODM\ReferenceOne(targetDocument: Dummy::class, cascade: ['persist'], inversedBy: 'relatedOwnedDummy', storeAs: 'id')]
    public ?Dummy $owningDummy = null;

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
     * Get owning dummy.
     */
    public function getOwningDummy(): ?Dummy
    {
        return $this->owningDummy;
    }

    /**
     * Set owning dummy.
     *
     * @param Dummy $owningDummy the value to set
     */
    public function setOwningDummy(Dummy $owningDummy): void
    {
        $this->owningDummy = $owningDummy;
    }
}
