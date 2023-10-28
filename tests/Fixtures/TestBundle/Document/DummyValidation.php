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
    new Post(uriTemplate: 'dummy_validation{._format}'),
    new Post(uriTemplate: '/dummy_validation/validation_groups', validationContext: ['groups' => ['a']], extraProperties: ['rfc_7807_compliant_errors' => false]),
    new Post(uriTemplate: '/dummy_validation/validation_sequence', validationContext: ['groups' => 'app.dummy_validation.group_generator'], extraProperties: ['rfc_7807_compliant_errors' => false]),
]
)]
#[ODM\Document]
class DummyValidation
{
    /**
     * @var int|null The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    /**
     * @var string|null The dummy name
     */
    #[Assert\NotNull(groups: ['a'])]
    #[ODM\Field(nullable: true)]
    private ?string $name = null;
    /**
     * @var string|null The dummy title
     */
    #[Assert\NotNull(groups: ['b'])]
    #[ODM\Field(nullable: true)]
    private ?string $title = null;
    /**
     * @var string|null The dummy code
     */
    #[ODM\Field]
    private ?string $code = null;

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

    /**
     * @param string|null $name
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code): self
    {
        $this->code = $code;

        return $this;
    }
}
