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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(operations: [
    new GetCollection(),
    new Post(uriTemplate: '/dummy_validation_reversed_order/validation_groups', validationContext: ['groups' => ['a']]),
    new Post(uriTemplate: 'dummy_validation_reversed_order{._format}'),
]
)]
#[ODM\Document]
class DummyValidationReversedOrder
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    #[Assert\NotNull(groups: ['a'])]
    #[ODM\Field(nullable: true)]
    private ?string $name = null;
    #[Assert\NotNull(groups: ['b'])]
    #[ODM\Field(nullable: true)]
    private ?string $title = null;
    #[ODM\Field]
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
