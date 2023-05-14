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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Custom Normalized Dummy.
 *
 * @author Mikaël Labrut <labrut@gmail.com>
 */
#[ApiResource(normalizationContext: ['groups' => ['output']], denormalizationContext: ['groups' => ['input']], extraProperties: ['standard_put' => false])]
#[ORM\Entity]
class CustomNormalizedDummy
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['input', 'output'])]
    private ?int $id = null;

    /**
     * @var string The dummy name
     */
    #[ApiProperty(types: ['https://schema.org/name'])]
    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups(['input', 'output'])]
    private string $name;

    /**
     * @var string|null The dummy name alias
     */
    #[ApiProperty(types: ['https://schema.org/alternateName'])]
    #[ORM\Column(nullable: true)]
    #[Groups(['input', 'output'])]
    private ?string $alias = null;

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
