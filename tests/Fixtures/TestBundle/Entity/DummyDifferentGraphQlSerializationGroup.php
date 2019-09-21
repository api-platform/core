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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Dummy with different serialization groups for item_query and collection_query.
 *
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 *
 * @ApiResource(
 *     graphql={
 *         "item_query"={"normalization_context"={"groups"={"item_query"}}},
 *         "collection_query"={"normalization_context"={"groups"={"collection_query"}}}
 *     }
 * )
 * @ORM\Entity
 */
class DummyDifferentGraphQlSerializationGroup
{
    /**
     * @var int The id
     *
     * @ORM\Column(type="integer", nullable=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"item_query", "collection_query"})
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ORM\Column
     * @Groups({"item_query", "collection_query"})
     */
    private $name;

    /**
     * @var string The dummy title
     *
     * @ORM\Column(nullable=true)
     * @Groups({"item_query"})
     */
    private $title;

    public function getId(): int
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
