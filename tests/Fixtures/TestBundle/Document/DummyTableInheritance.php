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

#[ApiResource]
#[ODM\Document]
#[ODM\InheritanceType('SINGLE_COLLECTION')]
#[ODM\DiscriminatorField(value: 'discr')]
#[ODM\DiscriminatorMap(['dummyTableInheritance' => DummyTableInheritance::class, 'dummyTableInheritanceChild' => DummyTableInheritanceChild::class, 'dummyTableInheritanceDifferentChild' => DummyTableInheritanceDifferentChild::class, 'dummyTableInheritanceNotApiResourceChild' => DummyTableInheritanceNotApiResourceChild::class])]
class DummyTableInheritance
{
    /**
     * @var int|null The id
     */
    #[Groups(['default'])]
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    /**
     * @var string|null The dummy name
     */
    #[Groups(['default'])]
    #[ODM\Field]
    private ?string $name = null;
    #[ODM\ReferenceOne(targetDocument: DummyTableInheritanceRelated::class, inversedBy: 'children')]
    private ?DummyTableInheritanceRelated $parent = null;

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
