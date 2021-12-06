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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Custom Normalized Dummy.
 *
 * @author Mikaël Labrut <labrut@gmail.com>
 *
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"output"}},
 *     "denormalization_context"={"groups"={"input"}}
 * })
 * @ORM\Entity
 */
class CustomNormalizedDummy
{
    /**
     * @var int|null The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"input", "output"})
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ORM\Column
     * @Assert\NotBlank
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"input", "output"})
     */
    private $name;

    /**
     * @var string|null The dummy name alias
     *
     * @ORM\Column(nullable=true)
     * @ApiProperty(iri="https://schema.org/alternateName")
     * @Groups({"input", "output"})
     */
    private $alias;

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

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }

    public function getPersonalizedAlias(): string
    {
        return $this->alias;
    }

    public function setPersonalizedAlias(string $value): void
    {
        $this->alias = $value;
    }
}
