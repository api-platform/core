# Filtering system and query parameters

* Deciders: @dunglas, @soyuka
* Consulted: @aegypius, @mrossard, @metaclass-nl, @helyakin
* Informed: @jdeniau, @bendavies

## Context and Problem Statement

Over the year we collected lots of issues and behaviors around filter composition, query parameters documentation and validation. A [Github issue](https://github.com/api-platform/core/issues/2400) tracks these problems or enhancements. Today, an API Filter is defined by this interface: 

```php
/**
 * Filters applicable on a resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface FilterInterface
{
    /**
     * Gets the description of this filter for the given resource.
     *
     * Returns an array with the filter parameter names as keys and array with the following data as values:
     *   - property: the property where the filter is applied
     *   - type: the type of the filter
     *   - required: if this filter is required
     *   - strategy (optional): the used strategy
     *   - is_collection (optional): if this filter is for collection
     *   - swagger (optional): additional parameters for the path operation,
     *     e.g. 'swagger' => [
     *       'description' => 'My Description',
     *       'name' => 'My Name',
     *       'type' => 'integer',
     *     ]
     *   - openapi (optional): additional parameters for the path operation in the version 3 spec,
     *     e.g. 'openapi' => [
     *       'description' => 'My Description',
     *       'name' => 'My Name',
     *       'schema' => [
     *          'type' => 'integer',
     *       ]
     *     ]
     *   - schema (optional): schema definition,
     *     e.g. 'schema' => [
     *       'type' => 'string',
     *       'enum' => ['value_1', 'value_2'],
     *     ]
     * The description can contain additional data specific to a filter.
     *
     * @see \ApiPlatform\OpenApi\Factory\OpenApiFactory::getFiltersParameters
     */
    public function getDescription(string $resourceClass): array;
}
```

The idea of this ADR is to find a way to introduce more functionalities to API Platform filters such as: 

- document query parameters for hydra, JSON Schema (OpenAPI being an extension of JSON Schema). 
- pilot the query parameter validation (current QueryParameterValidator bases itself on the given documentation schema) this is good but lacks flexibility when you need custom validation (created by @jdeniau)
- compose with filters, which will naturally help creating an or/and filter
- reduce the strong link between a query parameter and a property (they may have different names [#5980][pull/5980]), different types, a query parameter can have no link with a property (order filter). We still keep that link as inspired by [Hydra property search][hydra]
- provide a way to implement different query parameter syntaxes without changing the Filter implementation behind it

We will keep a BC layer with the current doctrine system as it shouldn't change much.

### Filter composition

For this to work, we need to consider a 4 year old bug on searching with UIDs. Our SearchFilter allows to search by `propertyName` or by relation, using either a scalar or an IRI: 

```
/books?author.id=1
/books?author.id=/author/1
```

Many attempts to fix these behaviors on API Platform have lead to bugs and to be reverted. The proposal is to change how filters are applied to provide filters with less logic, that are easier to maintain and that do one thing good. 

For the following example we will use an UUID to represent the stored identifier of an Author resource.

We know `author` is a property of `Book`, that represents a Resource. So it can be filtered by:

- IRI
- uid

We should therefore call both of these filters for each query parameter matched: 

- IriFilter (will do nothing if the value is not an IRI)
- UuidFilter

With that in mind, an `or` filter would call a bunch of filters specifying the logic operation to execute. 

### Query parameter

The above shows that a query parameter **key**, which is a `string`, may lead to multiple filters being called. The same can represent one or multiple values, and for a same **key** we can handle multiple types of data.
Also, if someone wants to implement the [loopback API](https://loopback.io/doc/en/lb2/Fields-filter.html) `?filter[fields][vin]=false` the link between the query parameter, the filter and the value gets more complex. 

We need a way to instruct the program to parse query parameters and produce a link between filters, values and some context (property, logical operation, type etc.). The same system could be used to determine the **type** a **filter** must have to pilot query parameter validation and the JSON Schema. 

## Considered Options

Let's define a new Attribute `Parameter` that holds informations (filters, context, schema) tight to a parameter `key`. 

```php
namespace ApiPlatform\Metadata;

use ApiPlatform\OpenApi;

final class Parameter {
    public string $key;
    public \ArrayObject schema;
    public array $context;
    public OpenApi\Parameter $openApi;
    public string|callable provider(): Operation;
    // filter service id
    public string $filter;
}
```

By default applied to a class, the `Parameter` would apply on every operations, or it could be specified on a single operation: 

```php
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Doctrine\Common\AndParameter;

#[GetCollection(parameters: ['and' => new AndParameter])]
#[AndParameter('and')]
class Book {}
```

API Platform will continue to provide parsed query parameters and set an `_api_query_parameters` Request attribute, in the end the filter may or may not use it:

```php
$queryString = RequestParser::getQueryString($request);
$request->attributes->set('_api_query_parameters', $queryString ? RequestParser::parseRequestParams($queryString) : []);
```

On top of that we will provide an additional `_api_header_parameters` as we would like to introduce a `QueryParameter` and a `HeaderParameter`. 

### Parameter Provider

During the `Provider` phase (`RequestEvent::REQUEST`), we could use a `ParameterProvider`:

```php
/**
 * Optionnaly transforms request parameters and provides modification to the current Operation.
 *
 * @implements ProviderInterface<HttpOperation>
 */
interface ParameterProvider extends ProviderInterface {
    public function provider(HttpOperation $operation, array $uriVariables = [], array $context = []): HttpOperation;
}
```

This provider can: 

1. alter the HTTP Operation to provide additional context:

```php
class GroupsParameterProvider implements ProviderInterface {
    public function provider(Operation $operation, array $uriVariables = [], array $context = []): HttpOperation 
    {
        $request = $context['request'];
        return $operation->withNormalizationContext(['groups' => $request->query->all('groups')]);
    }
}
```

2. alter the parameter context:

```php
class UuidParameter implements ProviderInterface {
    public function provider(Operation $operation, array $uriVariables = [], array $context = []): HttpOperation 
    {
        $request = $context['request'];
        $parameters = $request->attributes->get('_api_query_parameters');
        foreach ($parameters as $key => $value) {
            $parameter = $operation->getParameter($key);
            if (!$parameter) {
                continue;
            }

            if (!in_array('uuid', $parameter->getSchema()['type'])) {
                continue;
            }

            // TODO: should handle array values
            try {
                $parameters[$key] = Uuid::fromString($value);
            } catch (\Exception $e) {}
            
            if ($parameter->getFilter() === SearchFilter::class) {
                // Additionnaly, we are now sure we want an uuid filter so we could change it:
                $operation->withParameter($key, $parameter->withFilter(UuidFilter::class));
            }
        }

        return $operation;
    }
}
```

3. Validate parameters through the ParameterValidator.

### Filters

Filters should remain mostly unchanged, the current informations about the `property` to filter should also be specified inside a `Parameter`.
They alter the Doctrine/Elasticsearch Query, therefore we need one interface per persistence layer supported. The current logic within API Platform is:

```php
// src/Doctrine/Orm/Extension/FilterExtension.php
foreach ($operation->getFilters() ?? [] as $filterId) {
    $filter = $this->filterLocator->has($filterId) ? $this->filterLocator->get($filterId) : null;
    if ($filter instanceof FilterInterface) {
        // Apply the OrderFilter after every other filter to avoid an edge case where OrderFilter would do a LEFT JOIN instead of an INNER JOIN
        if ($filter instanceof OrderFilter) {
            $orderFilters[] = $filter;
            continue;
        }

        $context['filters'] ??= [];
        $filter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
    }
}
```

As we want a parameter to have some filters, we'd add the same logic based on the parameters `filter` information, for example:

```php
// src/Doctrine/Orm/Extension/ParameterExtension.php
$values = $request->attributes->get('_api_query_parameters');
foreach ($operation->getParameters() as $key => $parameter) {
    if (!array_key_exists($key, $values) || !($filterId = $parameter->getFilter())) {
        continue;
    }

    $filter = $this->filterLocator->has($filterId) ? $this->filterLocator->get($filterId) : null;

    if ($filter instanceof FilterInterface) {
        $context['parameter'] = $parameter;
        $context['value'] = $values[$key];
        $filter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
    }
}
```

- A `Parameter` doesn't necessary have a filter.
- Any logic regarding order of filters needs to be handled by the callee (just as above). 
- For filter composition we may introduce an `OrFilter` or `AndFilter` on an `or` or `and` parameter that would be exposed for users to use.

## Links

* [Filter composition][pull/2400]
* [Hydra property search](hydra)

[pull/5980]: https://github.com/api-platform/core/pull/5980  "ApiFilter does not respect SerializerName"
[pull/2400]: https://github.com/api-platform/core/pull/2400  "Filter composition"
[hydra]: http://www.hydra-cg.com/spec/latest/core/#supported-property-data-source "Hydra property data source"
