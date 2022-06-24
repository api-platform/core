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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Mapping as ORM;

/**
 * Related Owned Dummy.
 *
 * @author Sergey V. Ryabov <sryabov@mhds.ru>
 */
#[ApiResource(types: ['https://schema.org/Product'])]
#[ORM\Entity]
class RelatedOwnedDummy
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;
    /**
     * @var string|null A name
     */
    #[ORM\Column(nullable: true)]
    public $name;
    /**
     * @var Dummy
     */
    #[ORM\OneToOne(targetEntity: Dummy::class, cascade: ['persist'], inversedBy: 'relatedOwnedDummy')]
    #[ORM\JoinColumn(nullable: false)]
    public $owningDummy;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
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
