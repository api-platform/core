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
     * @var int|null The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     *
     * @Groups({"default"})
     */
    private $id;

    /**
     * @var string|null The dummy name
     *
     * @ODM\Field
     *
     * @Groups({"default"})
     */
    private $name;

    /**
     * @var DummyTableInheritanceRelated|null
     *
     * @ODM\ReferenceOne(targetDocument=DummyTableInheritanceRelated::class, inversedBy="children")
     */
    private $parent;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?DummyTableInheritanceRelated
    {
        return $this->parent;
    }

    public function setParent(DummyTableInheritanceRelated $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
