<?php
// ---
// slug: create-a-custom-elasticsearch-filter
// name: Create a Custom Elasticsearch Filter
// executable: false
// tags: elasticsearch
// ---

// Elasticsearch filters have access to the context created from the HTTP request and to the Elasticsearch query clause. They are only applied to collections. If you want to deal with the query DSL through the search request body, extensions are the way to go.
//
// Existing Elasticsearch filters are applied through a [constant score query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-constant-score-query.html). A constant score query filter is basically a class implementing the `ApiPlatform\Elasticsearch\Filter\ConstantScoreFilterInterface` and the `ApiPlatform\Elasticsearch\Filter\FilterInterface`. API Platform includes a convenient abstract class implementing this last interface and providing utility methods: `ApiPlatform\Elasticsearch\Filter\AbstractFilter`.
//
// Suppose you want to use the [match filter](/docs/reference/Elasticsearch/Filter/MatchFilter) on a property named `$title` and you want to add the [`and` operator](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html#query-dsl-match-query-boolean) to your query:

namespace App\ElasticSearch {
    use ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface;
    use ApiPlatform\Metadata\Operation;

    /*
     * Thanks to Symfony autowiring and autoconfiguration, this extension is automatically declared as a service.
     */
    final class AndOperatorFilterExtension implements RequestBodySearchCollectionExtensionInterface
    {
        public function applyToCollection(array $requestBody, string $resourceClass, ?Operation $operation = null, array $context = []): array
        {
            $requestBody['query'] = $requestBody['query'] ?? [];
            $requestBody['query']['constant_score']['filter']['bool']['must'][0]['match']['title'] = [
                'query' => $context['filters']['title'],
                'operator' => 'and',
            ];

            return $requestBody;
        }
    }
}
