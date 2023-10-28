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
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * DummyProperty.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
#[ApiResource(graphQlOperations: [new Query(name: 'item_query'), new QueryCollection(name: 'collection_query'), new Mutation(name: 'update'), new Mutation(name: 'delete'), new Mutation(name: 'create', normalizationContext: ['groups' => ['dummy_graphql_read']])], normalizationContext: ['groups' => ['dummy_read']], denormalizationContext: ['groups' => ['dummy_write']], filters: ['dummy_property.property', 'dummy_property.whitelist_property', 'dummy_property.whitelisted_properties'])]
#[ORM\Entity]
class DummyProperty
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['dummy_read', 'dummy_graphql_read'])]
    private ?int $id = null;
    /**
     * @var string|null
     */
    #[ORM\Column(nullable: true)]
    #[Groups(['dummy_read', 'dummy_write'])]
    public $foo;
    /**
     * @var string|null
     */
    #[ORM\Column(nullable: true)]
    #[Groups(['dummy_read', 'dummy_graphql_read', 'dummy_write'])]
    public $bar;
    /**
     * @var string|null
     */
    #[ORM\Column(nullable: true)]
    #[Groups(['dummy_read', 'dummy_graphql_read', 'dummy_write'])]
    public $baz;
    /**
     * @var DummyGroup|null
     */
    #[ORM\ManyToOne(targetEntity: DummyGroup::class, cascade: ['persist'])]
    #[Groups(['dummy_read', 'dummy_graphql_read', 'dummy_write'])]
    public $group;
    #[ORM\ManyToMany(targetEntity: DummyGroup::class, cascade: ['persist'])]
    #[Groups(['dummy_read', 'dummy_graphql_read', 'dummy_write'])]
    public Collection|iterable|null $groups = null;
    /**
     * @var string|null
     */
    #[ORM\Column(nullable: true)]
    #[Groups(['dummy_read'])]
    public $nameConverted;

    public function getId(): ?int
    {
        return $this->id;
    }
}
