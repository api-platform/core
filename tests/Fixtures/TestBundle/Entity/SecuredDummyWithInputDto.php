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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\SecuredDummyInputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\State\SecuredDummyDtoInputProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Resource with input DTO and secured properties.
 */
#[ApiResource(
    operations: [
        new Get(uriTemplate: 'secured_dummies_with_input_dto/{id}'),
        new GetCollection(uriTemplate: 'secured_dummies_with_input_dto'),
        new Post(uriTemplate: 'secured_dummies_with_input_dto'),
        new Put(uriTemplate: 'secured_dummies_with_input_dto/{id}'),
    ],
    input: SecuredDummyInputDto::class,
    processor: SecuredDummyDtoInputProcessor::class)
]
#[ORM\Entity]
class SecuredDummyWithInputDto
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * @var string The title
     */
    #[ORM\Column]
    #[Assert\NotBlank]
    private string $title;

    /**
     * @var string The description
     */
    #[ORM\Column]
    private string $description = '';

    #[ORM\Column(nullable: true)]
    private ?string $adminOnlyProperty = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getAdminOnlyProperty(): ?string
    {
        return $this->adminOnlyProperty;
    }

    public function setAdminOnlyProperty(?string $adminOnlyProperty): void
    {
        $this->adminOnlyProperty = $adminOnlyProperty;
    }
}
