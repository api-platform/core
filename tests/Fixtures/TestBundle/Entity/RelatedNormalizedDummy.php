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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Related to Normalized Dummy.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
#[ApiResource(normalizationContext: ['groups' => ['related_output', 'output']], denormalizationContext: ['groups' => ['related_input', 'input']])]
#[ORM\Entity]
class RelatedNormalizedDummy
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['related_output', 'related_input'])]
    private ?int $id = null;

    /**
     * @var string The dummy name
     */
    #[ApiProperty(types: ['https://schema.org/name'])]
    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups(['related_output', 'related_input'])]
    private string $name;
    /**
     * @var Collection<int, CustomNormalizedDummy> Several Normalized dummies
     */
    #[ORM\ManyToMany(targetEntity: CustomNormalizedDummy::class)]
    #[Groups(['related_output', 'related_input'])]
    public Collection|iterable $customNormalizedDummy;

    public function __construct()
    {
        $this->customNormalizedDummy = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCustomNormalizedDummy(): Collection|iterable
    {
        return $this->customNormalizedDummy;
    }

    public function setCustomNormalizedDummy(Collection|iterable $customNormalizedDummy): void
    {
        $this->customNormalizedDummy = $customNormalizedDummy;
    }
}
