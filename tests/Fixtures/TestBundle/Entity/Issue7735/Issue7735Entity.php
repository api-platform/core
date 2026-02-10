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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7735;

use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7735\Issue7735Resource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[Map(target: Issue7735Resource::class)]
class Issue7735Entity
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    #[Map(transform: 'strval')]
    private ?int $id = null;

    #[ORM\Column]
    private string $name;

    /**
     * This property is nullable to allow ObjectMapper to set null during initial mapping.
     * It will be set to a non-null value in the PrePersist callback.
     */
    #[ORM\Column]
    private ?string $generatedValue = null;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        // Initialize the typed property in PrePersist
        $this->generatedValue = 'generated_'.uniqid();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getGeneratedValue(): ?string
    {
        return $this->generatedValue;
    }

    public function setGeneratedValue(?string $generatedValue): self
    {
        $this->generatedValue = $generatedValue;

        return $this;
    }
}
