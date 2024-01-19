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

namespace ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Composite Item.
 */
#[ApiResource]
#[ORM\Entity]
class CompositeItem implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['default'])]
    private ?string $field1 = null;
    #[ORM\OneToMany(targetEntity: CompositeRelation::class, mappedBy: 'compositeItem', fetch: 'EAGER')]
    #[Groups(['default'])]
    private Collection|iterable $compositeValues;

    public function __construct()
    {
        $this->compositeValues = new ArrayCollection();
    }

    /**
     * Gets id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets field1.
     */
    public function getField1(): ?string
    {
        return $this->field1;
    }

    /**
     * Sets field1.
     *
     * @param string|null $field1 the value to set
     */
    public function setField1($field1 = null): void
    {
        $this->field1 = $field1;
    }

    /**
     * Gets compositeValues.
     */
    public function getCompositeValues(): Collection|iterable
    {
        return $this->compositeValues;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
