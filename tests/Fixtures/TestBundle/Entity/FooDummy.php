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
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * FooDummy.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
#[ApiResource(graphQlOperations: [new QueryCollection(name: 'collection_query', paginationType: 'page'), new Mutation(name: 'update')], order: ['dummy.name'])]
#[ORM\Entity]
class FooDummy
{
    /**
     * @var int The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * @var string The foo name
     */
    #[ORM\Column]
    private $name;

    #[ORM\Column(nullable: true)]
    private $nonWritableProp;

    #[ApiProperty(readableLink: true, writableLink: true)]
    #[ORM\Embedded(class: FooEmbeddable::class)]
    private ?FooEmbeddable $embeddedFoo = null;

    /**
     * @var Dummy|null The foo dummy
     */
    #[ORM\ManyToOne(targetEntity: Dummy::class, cascade: ['persist'])]
    private ?Dummy $dummy = null;

    /**
     * @var Collection<SoMany>
     */
    #[ORM\OneToMany(targetEntity: SoMany::class, mappedBy: 'fooDummy', cascade: ['persist'])]
    public Collection $soManies;

    public function __construct()
    {
        $this->nonWritableProp = 'readonly';
        $this->soManies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNonWritableProp()
    {
        return $this->nonWritableProp;
    }

    public function getDummy(): ?Dummy
    {
        return $this->dummy;
    }

    public function getEmbeddedFoo(): ?FooEmbeddable
    {
        return $this->embeddedFoo && !$this->embeddedFoo->getDummyName() && !$this->embeddedFoo->getNonWritableProp() ? null : $this->embeddedFoo;
    }

    public function setEmbeddedFoo(?FooEmbeddable $embeddedFoo): self
    {
        $this->embeddedFoo = $embeddedFoo;

        return $this;
    }

    public function setDummy(Dummy $dummy): void
    {
        $this->dummy = $dummy;
    }
}
