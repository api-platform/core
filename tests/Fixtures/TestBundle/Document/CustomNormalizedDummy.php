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
 * @ODM\Document
 */
#[ApiResource(normalizationContext: ['groups' => ['output']], denormalizationContext: ['groups' => ['input']])]
class CustomNormalizedDummy
{
    /**
     * @var int|null The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    #[Groups(['input', 'output'])]
    private ?int $id = null;

    /**
     * @var string|null The dummy name
     *
     * @ODM\Field
     */
    #[ApiProperty(types: ['http://schema.org/name'])]
    #[Assert\NotBlank]
    #[Groups(['input', 'output'])]
    private ?string $name = null;

    /**
     * @var string|null The dummy name alias
     *
     * @ODM\Field(nullable=true)
     */
    #[ApiProperty(types: ['http://schema.org/alternateName'])]
    #[Groups(['input', 'output'])]
    private ?string $alias = null;

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

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     */
    public function setAlias($alias)
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
    public function setPersonalizedAlias($value)
    {
        $this->alias = $value;
    }
}
