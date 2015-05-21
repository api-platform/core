# Filters

The bundle provides a generic system to apply filters on collections. It ships with built-in Doctrine ORM support
and can be extended to fit your specific needs.

By default, all filters are disabled. They must be enabled manually.

When a filter is enabled, it is automatically documented as a `hydra:search` property in collection returns. It also automatically
appears in the NelmioApiDoc documentation if this bundle is installed and enabled.

## Adding Doctrine ORM filters

If Doctrine ORM support is enabled, adding filters is as easy as adding an entry in your `app/config/services.yml` file.
It supports exact and partial matching strategies. If the partial strategy is specified, a SQL query with a `LIKE %text to search%`
query will be automatically issued.

To allow filtering the list of offers:

```yaml
# app/config/services.yml

services:
    resource.offer.search_filter:
        parent:    "api.doctrine.orm.search_filter"
        arguments: [ {
                        "id": "exact",    # Filters on the id property, allow both numeric values and IRIs
                        "price": "exact", # Extracts all collection elements with the exact given price
                        "name": "partial" # Elements with given text in their name
                   } ]

    resource.offer:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Offer" ]
        calls:
            -      method:    "addFilter"
                   arguments: [ "@resource.offer.search_filter" ]
        tags:      [ { name: "api.resource" } ]
```

`http://localhost:8000/api/offers?price=10` will return all offers with a price being exactly `10`.
`http://localhost:8000/api/offers?name=shirt` will returns all offer with a description containing the word "shirt".

Filters can be combined together: `http://localhost:8000/api/offers?price=10&name=shirt`

It also possible to filter by relations:

```yaml
# app/config/services.yml

services:
    resource.offer.search_filter:
        parent:    "api.doctrine.orm.search_filter"
        arguments: [ { "product": "exact" } ]

    resource.offer:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Offer"] 
        calls:
            -      method:    "addFilter"
                   arguments: [ "@resource.offer.search_filter" ]
        tags:      [ { name: "api.resource" } ]
```

With this service definition, it is possible to find all offers for the given product.
Try the following: `http://localhost:8000/api/offers?product=/api/products/12`
Using a numeric ID will also work: `http://localhost:8000/api/offers?product=12`

It will return all offers for the product having the JSON-LD identifier (`@id`) `http://localhost:8000/api/products/12`.

The last possibility offered by the bundle is to enable filters on all properties exposed by the bundle by omitting the
first argument of the filter:

```yaml
# app/config/services.yml

services:
    resource.offer.search_filter:
        parent:    "api.doctrine.orm.search_filter"

    resource.offer:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Offer"] 
        calls:
            -      method:    "addFilter"
                   arguments: [ "@resource.offer.search_filter" ]
        tags:      [ { name: "api.resource" } ]
```

## Doctrine ORM order filter

This filter allows you to order a collection.

Syntax: `?order[property]=<asc|desc>`

### Basic usage

Enable the filter:

```yaml
# app/config/services.yml

services:
    resource.offer.order_filter:
        parent:    "api.doctrine.orm.order_filter"
        arguments: [ ["id", "name"] ]

    resource.offer:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Offer"] 
        calls:
            -      method:    "addFilter"
                   arguments: [ "@resource.offer.order_filter" ]
        tags:      [ { name: "api.resource" } ]
```

Given the collection endpoint is `/offers`, you can filter offers by name in ascending order and then by ID on descending order with the following query:

`/offers?order[name]=desc&order[id]=asc`.

### Advance usage

#### Modes

The filter can be either enabled on all properties or on specific properties.

```yaml
# app/config/services.yml

services:
    # Filter enbabled on all properties
    resource.offer.order_filter:
        parent:    "api.doctrine.orm.order_filter"

    # Filter enbabled on the properties `id` and `name`
    resource.offer.order_filter:
        parent:    "api.doctrine.orm.order_filter"
        arguments: [ ["id", "name"] ]

```

Regardless of the mode, the filter works on a property only if the property does exist and if the order value is valid (`asc` or `desc` case insensitive). When the property does not exist, is not enabled or the value incorrect, the query for this property is silently ignored.

#### Filter parameter

A problem which may be risen by using this filter is that the word `order` becomes a keyword and can no longer be used as a query parameter on your collection. If you are in this case, you can easily change the keyword used by specifying the following in your `app/config/config.yml`:

```yaml
# app/config/config.yml

dunglas_api:
    #...
    collection:
        filter_name:
            order:   "_order" # now to use the filter, you will have to use the `_order` keyword
        #...
```

#### Extending filter

The filter is pretty flexible: it has different modes and you can specify the keyword. But what if you want to completly change the syntax to use something like this:

`?filter[order][property]`

To do so you can extend `Dunglas\ApiBundle\Doctrine\Orm\OrderFilter` and override the `::apply()` method. The whole logic of the ordering is done in the protected `::applyFilter()`. So if the syntax is the only thing you wish to change, the following will be enough:

```php
<?php

# src/AppBundle/Doctrine/Orm/Filter/CustomOrderFilter.php

namespace AppBundle\Doctrine\Orm\Filter;

class CustomOrderFilter extends \Dunglas\ApiBundle\Doctrine\Orm\OrderFilter
{
    public function apply(ResourceInterface $resource, QueryBuilder $queryBuilder, Request $request)
    {
        $filter = $request->query->get('filter');
        if (null !== $filter && true === array_key_exists('order', $filter)) {
            $this->applyFilter($resource, $queryBuilder, $filter['order']);
        }
    }
}
```

Then the only thing left is the set the server parameters `api.doctrine.orm.order_filter.class` to `AppBundle\Doctrine\Orm\Filter\CustomOrderFilter`.

```yaml
# app/config/services.yml

parameters:
    api.doctrine.orm.order_filter.class: "AppBundle\Doctrine\Orm\Filter\CustomOrderFilter"
```

## Creating custom filters

Custom filters can be written by implementing the `Dunglas\ApiBundle\Api\Filter\FilterInterface` interface or the `Dunglas\ApiBundle\Doctrine\Orm\AbstractInterface`.
Doctrine ORM filters must implement the `Dunglas\ApiBundle\Doctrine\Orm\FilterInterface`. They can interact directly
with the Doctrine `QueryBuilder`.

Don't forget to register your custom filters with the `Dunglas\ApiBundle\Api\Resource::addFilter()` or `Dunglas\ApiBundle\Api\Resource::addFilters()` method.

If you use [custom data providers](data-providers.yml), they must support filtering and be aware of actives filters to
work properly.

Previous chapter: [Data providers](data-providers.md)<br>
Next chapter: [Serialization groups and relations](serialization-groups-and-relations.md)
