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
services:
    resource.offer.filter:
        parent:    "api.doctrine.orm.filter"
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
                   arguments:
                       -      "@resource.offer.filter"
        tags:      [ { name: "api.resource" } ]
```

`http://localhost:8000/api/offers?price=10` will return all offers with a price being exactly `10`.
`http://localhost:8000/api/offers?name=shirt` will returns all offer with a description containing the word "shirt".

Filters can be combined together: `http://localhost:8000/api/offers?price=10&name=shirt`

It also possible to filter by relations:

```yaml
services:
    resource.offer.filter:
        parent:    "api.doctrine.orm.filter"
        arguments: [ { "product": "exact" } ]

    resource.offer:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Offer"] 
        calls:
            -      method:    "addFilter"
                   arguments:
                       -      "@resource.offer.filter"
        tags:      [ { name: "api.resource" } ]
```

With this service definition, it is possible to find all offers for the given product.
Try the following: `http://localhost:8000/api/offers?product=/api/products/12`
Using a numeric ID will also work: `http://localhost:8000/api/offers?product=12`

It will return all offers for the product having the JSON-LD identifier (`@id`) `http://localhost:8000/api/products/12`.

The last possibility offered by the bundle is to enable filters on all properties exposed by the bundle by omitting the
first argument of the filter:

```yaml
services:
    resource.offer.filter:
        parent:    "api.doctrine.orm.filter"

    resource.offer:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Offer"] 
        calls:
            -      method:    "addFilter"
                   arguments:
                       -      "@resource.offer.filter"
        tags:      [ { name: "api.resource" } ]
```

## Creating custom filters

Custom filters can be written by implementing the `Dunglas\ApiBundle\Api\Filter\FilterInterface` interface.
Doctrine ORM filters must implement the `Dunglas\ApiBundle\Doctrine\Orm\FilterInterface`. They can interact directly
with the Doctrine `QueryBuilder`.

Don't forget to register your custom filters with the `Dunglas\ApiBundle\Api\Resource::addFilter()` method.
