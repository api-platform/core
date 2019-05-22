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
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(value="discr")
 * @ODM\DiscriminatorMap({
 *     "dummyTableInheritance"=DummyTableInheritance::class,
 *     "dummyTableInheritanceChild"=DummyTableInheritanceChild::class,
 *     "dummyTableInheritanceDifferentChild"=DummyTableInheritanceDifferentChild::class,
 *     "dummyTableInheritanceNotApiResourceChild"=DummyTableInheritanceNotApiResourceChild::class
 * })
 * @ApiResource
 */
class DummyTableInheritance
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     *
     * @Groups({"default"})
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ODM\Field
     *
     * @Groups({"default"})
     */
    private $name;

    /**
     * @var DummyTableInheritanceRelated
     *
     * @ODM\ReferenceOne(targetDocument=DummyTableInheritanceRelated::class, inversedBy="children")
     */
    private $parent;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DummyTableInheritanceRelated
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return $this
     */
    public function setParent(DummyTableInheritanceRelated $parent)
    {
        $this->parent = $parent;

        return $this;
    }
}
