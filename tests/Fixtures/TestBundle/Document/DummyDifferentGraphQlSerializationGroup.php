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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
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
 * @ODM\Document
 */
class DummyDifferentGraphQlSerializationGroup
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int", nullable=true)
     * @Groups({"item_query", "collection_query"})
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ODM\Field(type="string")
     * @Groups({"item_query", "collection_query"})
     */
    private $name;

    /**
     * @var string The dummy title
     *
     * @ODM\Field(nullable=true)
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

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
