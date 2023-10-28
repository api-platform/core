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
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * DummyProperty.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
#[ApiResource(graphQlOperations: [new Query(name: 'item_query'), new QueryCollection(name: 'collection_query'), new Mutation(name: 'update'), new Mutation(name: 'delete'), new Mutation(name: 'create', normalizationContext: ['groups' => ['dummy_graphql_read']])], normalizationContext: ['groups' => ['dummy_read']], denormalizationContext: ['groups' => ['dummy_write']], filters: ['dummy_property.property', 'dummy_property.whitelist_property', 'dummy_property.whitelisted_properties'])]
#[ODM\Document]
class DummyProperty
{
    #[Groups(['dummy_read', 'dummy_graphql_read'])]
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    /**
     * @var string|null
     */
    #[Groups(['dummy_read', 'dummy_write'])]
    #[ODM\Field(nullable: true)]
    public $foo;
    /**
     * @var string|null
     */
    #[Groups(['dummy_read', 'dummy_graphql_read', 'dummy_write'])]
    #[ODM\Field(nullable: true)]
    public $bar;
    /**
     * @var string|null
     */
    #[Groups(['dummy_read', 'dummy_graphql_read', 'dummy_write'])]
    #[ODM\Field(nullable: true)]
    public $baz;
    /**
     * @var DummyGroup|null
     */
    #[Groups(['dummy_read', 'dummy_graphql_read', 'dummy_write'])]
    #[ODM\ReferenceOne(targetDocument: DummyGroup::class, cascade: ['persist'], nullable: true)]
    public $group;
    /**
     * @var DummyGroup[]|null
     */
    #[Groups(['dummy_read', 'dummy_graphql_read', 'dummy_write'])]
    #[ODM\ReferenceMany(targetDocument: DummyGroup::class, cascade: ['persist'])]
    public Collection|iterable|null $groups = null;
    /**
     * @var string|null
     */
    #[Groups(['dummy_read', 'dummy_write'])]
    #[ODM\Field(nullable: true)]
    public $nameConverted;

    public function getId(): ?int
    {
        return $this->id;
    }
}
