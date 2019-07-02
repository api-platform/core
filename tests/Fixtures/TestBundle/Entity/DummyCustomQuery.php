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

/**
 * Dummy with custom GraphQL query resolvers.
 *
 * @author Lukas Lücke <lukas@luecke.me>
 *
 * @ApiResource(graphql={
 *     "testItem"={
 *         "item_query"="app.graphql.query_resolver.dummy_custom_item"
 *     },
 *     "testNotRetrievedItem"={
 *         "item_query"="app.graphql.query_resolver.dummy_custom_not_retrieved_item",
 *         "args"={}
 *     },
 *     "testItemCustomArguments"={
 *         "item_query"="app.graphql.query_resolver.dummy_custom_item",
 *         "args"={
 *             "id"={"type"="ID"},
 *             "customArgumentNullableBool"={"type"="Boolean"},
 *             "customArgumentBool"={"type"="Boolean!"},
 *             "customArgumentInt"={"type"="Int!"},
 *             "customArgumentString"={"type"="String!"},
 *             "customArgumentFloat"={"type"="Float!"},
 *             "customArgumentIntArray"={"type"="[Int!]!"},
 *             "customArgumentCustomType"={"type"="DateTime!"}
 *         }
 *     },
 *     "testCollection"={
 *         "collection_query"="app.graphql.query_resolver.dummy_custom_collection"
 *     },
 *     "testCollectionCustomArguments"={
 *         "collection_query"="app.graphql.query_resolver.dummy_custom_collection",
 *         "args"={
 *             "customArgumentString"={"type"="String!"}
 *         }
 *     }
 * })
 * @ORM\Entity
 */
class DummyCustomQuery
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @var string
     */
    public $message;

    /**
     * @var array
     */
    public $customArgs = [];
}
