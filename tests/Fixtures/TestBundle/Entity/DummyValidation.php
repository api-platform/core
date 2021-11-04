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

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ApiResource(
 *     collectionOperations={
 *         "get"={"method"="GET"},
 *         "post"={"path"="dummy_validation.{_format}", "method"="POST"},
 *         "post_validation_groups"={"route_name"="post_validation_groups", "validation_groups"={"a"}, "method"="GET"},
 *         "post_validation_sequence"={"route_name"="post_validation_sequence", "validation_groups"="app.dummy_validation.group_generator", "method"="GET"}
 *     }
 * )
 */
class DummyValidation
{
    /**
     * @var int|null The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string|null The dummy name
     *
     * @ORM\Column(nullable=true)
     * @Assert\NotNull(groups={"a"})
     */
    private $name;

    /**
     * @var string|null The dummy title
     *
     * @ORM\Column(nullable=true)
     * @Assert\NotNull(groups={"b"})
     */
    private $title;

    /**
     * @var string|null The dummy code
     * @ORM\Column
     */
    private $code;

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
