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
 *     "testCollection"={
 *         "collection_query"="app.graphql.query_resolver.dummy_custom_collection"
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
}
