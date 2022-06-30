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
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Dummy with different serialization groups for item_query and collection_query.
 *
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 */
#[ApiResource(graphQlOperations: [new Query(name: 'item_query', normalizationContext: ['groups' => ['item_query']]), new QueryCollection(name: 'collection_query', normalizationContext: ['groups' => ['collection_query']])])]
#[ODM\Document]
class DummyDifferentGraphQlSerializationGroup
{
    /**
     * @var int|null The id
     */
    #[Groups(['item_query', 'collection_query'])]
    #[ODM\Id(strategy: 'INCREMENT', type: 'int', nullable: true)]
    private ?int $id = null;
    /**
     * @var string|null The dummy name
     */
    #[Groups(['item_query', 'collection_query'])]
    #[ODM\Field(type: 'string')]
    private ?string $name = null;
    /**
     * @var string|null The dummy title
     */
    #[Groups(['item_query'])]
    #[ODM\Field(nullable: true)]
    private ?string $title = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
