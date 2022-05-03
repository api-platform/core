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

use ApiPlatform\Core\Annotation\ApiProperty;
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
 * @ORM\Entity
 */
#[ApiResource(normalizationContext: ['groups' => ['related_output', 'output']], denormalizationContext: ['groups' => ['related_input', 'input']])]
class RelatedNormalizedDummy
{
    /**
     * @var int|null The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"related_output", "related_input"})
     */
    private $id;
    /**
     * @var string The dummy name
     *
     * @ORM\Column
     * @Assert\NotBlank
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"related_output", "related_input"})
     */
    private $name;
    /**
     * @var Collection<int, CustomNormalizedDummy> Several Normalized dummies
     *
     * @ORM\ManyToMany(targetEntity="CustomNormalizedDummy")
     * @Groups({"related_output", "related_input"})
     */
    public $customNormalizedDummy;

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

    public function getCustomNormalizedDummy(): Collection
    {
        return $this->customNormalizedDummy;
    }

    public function setCustomNormalizedDummy($customNormalizedDummy): void
    {
        $this->customNormalizedDummy = $customNormalizedDummy;
    }
}
