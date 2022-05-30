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
use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dummy Friend.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource]
#[ORM\Entity]
class DummyFriend
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * @var string The dummy name
     */
    #[ApiProperty(types: ['http://schema.org/name'])]
    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups(['fakemanytomany', 'friends'])]
    private ?string $name = null;

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id the value to set
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param string $name the value to set
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }
}
