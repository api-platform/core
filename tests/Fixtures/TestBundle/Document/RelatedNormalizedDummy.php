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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Related to Normalized Dummy.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 *
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"related_output", "output"}},
 *     "denormalization_context"={"groups"={"related_input", "input"}}
 * })
 * @ODM\Document
 */
class RelatedNormalizedDummy
{
    /**
     * @var int|null The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     * @Groups({"related_output", "related_input"})
     */
    private $id;

    /**
     * @var string|null The dummy name
     *
     * @ODM\Field
     * @Assert\NotBlank
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"related_output", "related_input"})
     */
    private $name;

    /**
     * @var iterable Several Normalized dummies
     *
     * @ODM\ReferenceMany(targetDocument=CustomNormalizedDummy::class)
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

    /**
     * @param string $name
     */
    public function setName($name)
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

    public function setCustomNormalizedDummy(iterable $customNormalizedDummy): void
    {
        $this->customNormalizedDummy = $customNormalizedDummy;
    }
}
