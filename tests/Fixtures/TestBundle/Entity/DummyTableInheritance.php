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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *     "dummyTableInheritance"="DummyTableInheritance",
 *     "dummyTableInheritanceChild"="DummyTableInheritanceChild",
 *     "dummyTableInheritanceDifferentChild"="DummyTableInheritanceDifferentChild",
 *     "dummyTableInheritanceNotApiResourceChild"="DummyTableInheritanceNotApiResourceChild"
 * })
 * @ApiResource
 */
class DummyTableInheritance
{
    /**
     * @var int The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"default"})
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ORM\Column
     *
     * @Groups({"default"})
     */
    private $name;

    /**
     * @var DummyTableInheritanceRelated
     *
     * @ORM\ManyToOne(targetEntity="DummyTableInheritanceRelated", inversedBy="children")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
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
