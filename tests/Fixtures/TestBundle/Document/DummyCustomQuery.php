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
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Dummy with custom GraphQL query resolvers.
 *
 * @author Lukas Lücke <lukas@luecke.me>
 * @ODM\Document
 */
#[ApiResource(graphQlOperations: [new Query(name: 'testItem', resolver: 'app.graphql.query_resolver.dummy_custom_item'), new Query(name: 'testNotRetrievedItem', resolver: 'app.graphql.query_resolver.dummy_custom_not_retrieved_item_document', args: []), new Query(name: 'testNoReadAndSerializeItem', resolver: 'app.graphql.query_resolver.dummy_custom_item_no_read_and_serialize_document', read: false, serialize: false), new Query(name: 'testItemCustomArguments', resolver: 'app.graphql.query_resolver.dummy_custom_item', args: ['id' => ['type' => 'ID'], 'customArgumentNullableBool' => ['type' => 'Boolean'], 'customArgumentBool' => ['type' => 'Boolean!'], 'customArgumentInt' => ['type' => 'Int!'], 'customArgumentString' => ['type' => 'String!'], 'customArgumentFloat' => ['type' => 'Float!'], 'customArgumentIntArray' => ['type' => '[Int!]!'], 'customArgumentCustomType' => ['type' => 'DateTime!']]), new Query(name: 'testItemOutput', resolver: 'app.graphql.query_resolver.dummy_custom_item', output: OutputDto::class), new QueryCollection(name: 'testCollection', resolver: 'app.graphql.query_resolver.dummy_custom_collection'), new QueryCollection(name: 'testCollectionNoReadAndSerialize', resolver: 'app.graphql.query_resolver.dummy_custom_collection_no_read_and_serialize', read: false, serialize: false), new QueryCollection(name: 'testCollectionCustomArguments', resolver: 'app.graphql.query_resolver.dummy_custom_collection', args: ['customArgumentString' => ['type' => 'String!']])])]
class DummyCustomQuery
{
    /**
     * @var int
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
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
