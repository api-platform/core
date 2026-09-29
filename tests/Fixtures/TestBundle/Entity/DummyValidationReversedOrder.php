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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

// Same shape as DummyValidation, but the validationContext-bearing operation is
// declared BEFORE the plain one, to pin that definition names don't depend on
// which operation the OpenAPI document happens to build first (see #8119).
#[ApiResource(operations: [
    new GetCollection(),
    new Post(uriTemplate: '/dummy_validation_reversed_order/validation_groups', validationContext: ['groups' => ['a']]),
    new Post(uriTemplate: 'dummy_validation_reversed_order{._format}'),
]
)]
#[ORM\Entity]
class DummyValidationReversedOrder
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    #[ORM\Column(nullable: true)]
    #[Assert\NotNull(groups: ['a'])]
    private ?string $name = null;
    #[ORM\Column(nullable: true)]
    #[Assert\NotNull(groups: ['b'])]
    private ?string $title = null;
    #[ORM\Column]
    private string $code;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }
}
