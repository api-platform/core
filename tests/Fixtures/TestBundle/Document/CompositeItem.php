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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Composite Item.
 */
#[ApiResource]
#[ODM\Document]
class CompositeItem implements \Stringable
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    #[Groups(['default'])]
    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $field1 = null;
    #[Groups(['default'])]
    #[ODM\ReferenceMany(targetDocument: CompositeRelation::class, mappedBy: 'compositeItem')]
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
     */
    public function setField1(?string $field1 = null): void
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
