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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5793;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource(
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['write']],
)]
class BagOfTests
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read', 'write'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read', 'write'])]
    // ignore "type" schema property to ensure "schema" is properly overridden
    // see JsonSchemaGenerateCommandTest::testArraySchemaWithReference
    #[ApiProperty(schema: ['maxLength' => 255])]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'bagOfTests', targetEntity: TestEntity::class)]
    #[Groups(['read', 'write'])]
    #[ApiProperty(jsonSchemaContext: ['foo' => 'bar'], schema: ['type' => 'string'])]
    private Collection $tests;

    #[ORM\OneToMany(mappedBy: 'bagOfTests', targetEntity: NonResourceTestEntity::class)]
    #[Groups(['read', 'write'])]
    private Collection $nonResourceTests;

    #[ORM\ManyToOne(targetEntity: TestEntity::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'type', referencedColumnName: 'id', nullable: false)]
    #[Groups(['read', 'write'])]
    protected ?TestEntity $type = null;

    public function __construct()
    {
        $this->tests = new ArrayCollection();
        $this->nonResourceTests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, TestEntity>
     */
    public function getTests(): Collection
    {
        return $this->tests;
    }

    public function addTest(TestEntity $test): static
    {
        if (!$this->tests->contains($test)) {
            $this->tests->add($test);
            $test->setBagOfTests($this);
        }

        return $this;
    }

    public function removeTest(TestEntity $test): static
    {
        if ($this->tests->removeElement($test)) {
            // set the owning side to null (unless already changed)
            if ($test->getBagOfTests() === $this) {
                $test->setBagOfTests(null);
            }
        }

        return $this;
    }

    public function getNonResourceTests(): Collection
    {
        return $this->nonResourceTests;
    }

    public function addNonResourceTest(NonResourceTestEntity $nonResourceTest): static
    {
        if (!$this->nonResourceTests->contains($nonResourceTest)) {
            $this->nonResourceTests->add($nonResourceTest);
            $nonResourceTest->setBagOfTests($this);
        }

        return $this;
    }

    public function removeNonResourceTest(NonResourceTestEntity $nonResourceTest): static
    {
        if ($this->nonResourceTests->removeElement($nonResourceTest)) {
            // set the owning side to null (unless already changed)
            if ($nonResourceTest->getBagOfTests() === $this) {
                $nonResourceTest->setBagOfTests(null);
            }
        }

        return $this;
    }

    public function getType(): ?TestEntity
    {
        return $this->type;
    }

    public function setType(?TestEntity $type): self
    {
        $this->type = $type;

        return $this;
    }
}
