<?php

namespace ApiPlatform\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToApiResourceAttributeRector\Fixture;

use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(graphql={
 *     "sum"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom",
 *         "normalization_context"={"groups"={"result"}},
 *         "denormalization_context"={"groups"={"sum"}}
 *     },
 *     "sumNotPersisted"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom_not_persisted",
 *         "normalization_context"={"groups"={"result"}},
 *         "denormalization_context"={"groups"={"sum"}}
 *     },
 *     "sumNoWriteCustomResult"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom_no_write_custom_result",
 *         "normalization_context"={"groups"={"result"}},
 *         "denormalization_context"={"groups"={"sum"}},
 *         "write"=false
 *     },
 *     "sumOnlyPersist"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom_only_persist",
 *         "normalization_context"={"groups"={"result"}},
 *         "denormalization_context"={"groups"={"sum"}},
 *         "read"=false,
 *         "deserialize"=false,
 *         "validate"=false,
 *         "serialize"=false
 *     },
 *     "testCustomArguments"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom",
 *         "args"={"operandC"={"type"="Int!"}}
 *     },
 *     "testQuery"={
 *         "item_query"="app.graphql.query_resolver.dummy_custom",
 *         "serialize"=false
 *     },
 *     "testQueryCollection"={
 *         "collection_query"="app.graphql.collection_query_resolver.dummy_custom",
 *         "read"=false
 *     },
 *     "collection_query"={
 *         "pagination_enabled"=false
 *     },
 *     "create"
 * })
 */
class DummyCustomMutation
{
}

?>
-----
<?php

namespace ApiPlatform\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToApiResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
#[Get]
#[Put]
#[Patch]
#[Delete]
#[GetCollection]
#[Post]
#[Mutation(name: 'sum', resolver: 'app.graphql.mutation_resolver.dummy_custom', normalizationContext: ['groups' => ['result']], denormalizationContext: ['groups' => ['sum']])]
#[Mutation(name: 'sumNotPersisted', resolver: 'app.graphql.mutation_resolver.dummy_custom_not_persisted', normalizationContext: ['groups' => ['result']], denormalizationContext: ['groups' => ['sum']])]
#[Mutation(name: 'sumNoWriteCustomResult', resolver: 'app.graphql.mutation_resolver.dummy_custom_no_write_custom_result', write: false, normalizationContext: ['groups' => ['result']], denormalizationContext: ['groups' => ['sum']])]
#[Mutation(name: 'sumOnlyPersist', resolver: 'app.graphql.mutation_resolver.dummy_custom_only_persist', read: false, deserialize: false, validate: false, serialize: false, normalizationContext: ['groups' => ['result']], denormalizationContext: ['groups' => ['sum']])]
#[Mutation(name: 'testCustomArguments', resolver: 'app.graphql.mutation_resolver.dummy_custom', args: ['operandC' => ['type' => 'Int!']])]
#[Query(name: 'testQuery', serialize: false, resolver: 'app.graphql.query_resolver.dummy_custom')]
#[QueryCollection(name: 'testQueryCollection', read: false, resolver: 'app.graphql.collection_query_resolver.dummy_custom')]
#[QueryCollection(paginationEnabled: false)]
#[Mutation(name: 'create')]
class DummyCustomMutation
{
}

?>
