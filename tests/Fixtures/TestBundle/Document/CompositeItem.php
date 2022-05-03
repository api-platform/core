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
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Composite Item.
 */
#[ApiResource]
#[ODM\Document]
class CompositeItem implements \Stringable
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;
    #[Groups(['default'])]
    #[ODM\Field(type: 'string', nullable: true)]
    private $field1;
    #[Groups(['default'])]
    #[ODM\ReferenceMany(targetDocument: CompositeRelation::class, mappedBy: 'compositeItem')]
    private $compositeValues;

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
    public function setField1($field1 = null)
    {
        $this->field1 = $field1;
    }

    /**
     * Gets compositeValues.
     */
    public function getCompositeValues(): ?CompositeRelation
    {
        return $this->compositeValues;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
