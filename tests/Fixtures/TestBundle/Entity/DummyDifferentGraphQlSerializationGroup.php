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
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Dummy with different serialization groups for item_query and collection_query.
 *
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 */
#[ApiResource(graphQlOperations: [new Query(name: 'item_query', normalizationContext: ['groups' => ['item_query']]), new QueryCollection(name: 'collection_query', normalizationContext: ['groups' => ['collection_query']])])]
#[ORM\Entity]
class DummyDifferentGraphQlSerializationGroup
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['item_query', 'collection_query'])]
    private ?int $id = null;
    /**
     * @var string The dummy name
     */
    #[ORM\Column]
    #[Groups(['item_query', 'collection_query'])]
    private string $name;
    /**
     * @var string|null The dummy title
     */
    #[ORM\Column(nullable: true)]
    #[Groups(['item_query'])]
    private ?string $title = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
