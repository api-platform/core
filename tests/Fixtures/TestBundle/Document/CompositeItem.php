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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Composite Item.
 *
 * @ApiResource
 * @ODM\Document
 */
class CompositeItem
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @ODM\Field(type="string", nullable=true)
     * @Groups({"default"})
     */
    private $field1;

    /**
     * @ODM\ReferenceMany(targetDocument=CompositeRelation::class, mappedBy="compositeItem")
     * @Groups({"default"})
     */
    private $compositeValues;

    /**
     * Gets id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets field1.
     *
     * @return string|null
     */
    public function getField1()
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
     *
     * @return CompositeRelation
     */
    public function getCompositeValues()
    {
        return $this->compositeValues;
    }

    public function __toString()
    {
        return (string) $this->id;
    }
}
