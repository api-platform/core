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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Custom Normalized Dummy.
 *
 * @author Mikaël Labrut <labrut@gmail.com>
 */
#[ApiResource(normalizationContext: ['groups' => ['output']], denormalizationContext: ['groups' => ['input']], extraProperties: ['standard_put' => false])]
#[ODM\Document]
class CustomNormalizedDummy
{
    /**
     * @var int|null The id
     */
    #[Groups(['input', 'output'])]
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    /**
     * @var string|null The dummy name
     */
    #[ApiProperty(types: ['https://schema.org/name'])]
    #[Assert\NotBlank]
    #[Groups(['input', 'output'])]
    #[ODM\Field]
    private ?string $name = null;

    /**
     * @var string|null The dummy name alias
     */
    #[ApiProperty(types: ['https://schema.org/alternateName'])]
    #[Groups(['input', 'output'])]
    #[ODM\Field(nullable: true)]
    private ?string $alias = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name): void
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

    /**
     * @param string $alias
     */
    public function setAlias($alias): void
    {
        $this->alias = $alias;
    }

    public function getPersonalizedAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @param string $value
     */
    public function setPersonalizedAlias($value): void
    {
        $this->alias = $value;
    }
}
