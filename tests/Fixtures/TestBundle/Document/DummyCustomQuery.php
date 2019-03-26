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
 *         "item_query"="app.graphql.query_resolver.dummy_custom_not_retrieved_item_document",
 *         "args"={}
 *     },
 *     "testCollection"={
 *         "collection_query"="app.graphql.query_resolver.dummy_custom_collection"
 *     }
 * })
 * @ODM\Document
 */
class DummyCustomQuery
{
    /**
     * @var int
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    public $id;

    /**
     * @var string
     */
    public $message;
}
