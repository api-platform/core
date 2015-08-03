# Filters

The bundle provides a generic system to apply filters on collections. Useful filters
for the Doctrine ORM are provided with the bundle. However the filter system is
extensible enough to let you create custom filters fitting your specific needs
and for any data provider.

By default, all filters are disabled. They must be enabled explicitly.

When a filter is enabled, it is automatically documented as a `hydra:search` property
in collection returns. It also automatically appears in the NelmioApiDoc documentation
if this bundle is active.

## Search filter

If Doctrine ORM support is enabled, adding filters is as easy as adding an entry
in your `app/config/services.yml` file.

The search filter supports exact and partial matching strategies.
If the partial strategy is specified, a SQL query with a `WHERE` clause similar
to `LIKE %text to search%` will be automatically issued.

In the following, we will see how to allow filtering a list of e-commerce offers:

```yaml

# app/config/services.yml

services:
    resource.offer.search_filter:
        parent:    "api.doctrine.orm.search_filter"
        arguments: [ { id: "exact", price: "exact", name: "partial"  } ]

    resource.offer:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Offer" ]
        calls:
            -      method:    "initFilters"
                   arguments: [ [ "@resource.offer.search_filter" ] ]
        tags:      [ { name: "api.resource" } ]
```

`http://localhost:8000/api/offers?price=10` will return all offers with a price being exactly `10`.
`http://localhost:8000/api/offers?name=shirt` will returns all offer with a description containing the word "shirt".

Filters can be combined together: `http://localhost:8000/api/offers?price=10&name=shirt`

It is possible to filter on relations too:

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
            -      method:    "initFilters"
                   arguments: [ [ "@resource.offer.search_filter" ] ]
        tags:      [ { name: "api.resource" } ]
```

With this service definition, it is possible to find all offers belonging to the
product identified by a given IRI.
Try the following: `http://localhost:8000/api/offers?product=/api/products/12`
Using a numeric ID is also supported: `http://localhost:8000/api/offers?product=12`

Previous URLs will return all offers for the product having the following IRI as
JSON-LD identifier (`@id`): `http://localhost:8000/api/products/12`.

For to-many relations, just use the array syntax: `http://localhost:8000/api/offers?products[]=/api/products/13&products[]=/api/products/14`. This is translated into DQL as `products.id IN (13,14)`.

## Date filter

The date filter allows to filter a collection by date intervals.

Syntax: `?property[<after|before>]=value`

The value can take any date format supported by the [`\DateTime()`](http://php.net/manual/en/datetime.construct.php)
class.

As others filters, the date filter must be explicitly enabled:

```yaml

# app/config/services.yml

services:
    # Enable date filter for the property "dateProperty" of the resource "resource.offer"
    resource.date_filter:
        parent:    "api.doctrine.orm.date_filter"
        arguments: [ { "dateProperty": ~ } ]

    resource.offer:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Offer"] 
        calls:
            -      method:    "initFilters"
                   arguments: [ [ "@resource.offer.date_filter" ] ]
        tags:      [ { name: "api.resource" } ]
```

### Managing `null` values

The date filter is able to deal with date properties having `null` values.
Four behaviors are available at the property level of the filter:

| Description                          | Strategy to set                                                                                 |
|--------------------------------------|-------------------------------------------------------------------------------------------------|
| Use the default behavior of the DBMS | `null`                                                                                          |
| Exclude items                        | `Dunglas\ApiBundle\Doctrine\Orm\Filter\DateFilter::EXCLUDE_NULL` (`exclude_null`)               |
| Consider items as oldest             | `Dunglas\ApiBundle\Doctrine\Orm\Filter\DateFilter::INCLUDE_NULL_BEFORE` (`include_null_before`) |
| Consider items as youngest           | `Dunglas\ApiBundle\Doctrine\Orm\Filter\DateFilter::INCLUDE_NULL_AFTER` (`include_null_after`)   |

For instance, exclude entries with a property value of `null`, with the following service definition:

```yaml

# app/config/services.yml

services:
    resource.date_filter:
        parent:    "api.doctrine.orm.date_filter"
        arguments: [ { "dateProperty": exclude_null } ]

    resource.offer:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Offer"] 
        calls:
            -      method:    "initFilters"
                   arguments: [ [ "@resource.offer.date_filter" ] ]
        tags:      [ { name: "api.resource" } ]
```

If you use another service definition format than YAML, you can use the
`Dunglas\ApiBundle\Doctrine\Orm\Filter\DateFilter::EXCLUDE_NULL` constant directly.

## Order filter

The order filter allows to order a collection by given properties.

Syntax: `?order[property]=<asc|desc>`

Enable the filter:

```yaml

# app/config/services.yml

services:
    resource.offer.order_filter:
        parent:    "api.doctrine.orm.order_filter"
        arguments: [ { "id": ~, "name": ~ } ]

    resource.offer:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Offer"] 
        calls:
            -      method:    "initFilters"
                   arguments: [ [ "@resource.offer.order_filter" ] ]
        tags:      [ { name: "api.resource" } ]
```

Given that the collection endpoint is `/offers`, you can filter offers by name in
ascending order and then by ID on descending order with the following query: `/offers?order[name]=desc&order[id]=asc`.

By default, whenever the query does not specify explicitly the direction (e.g: `/offers?order[name]&order[id]`), filters will not be applied, unless you configure a default order direction to use:

```yaml

# app/config/services.yml

services:
    resource.offer.order_filter:
        parent:    "api.doctrine.orm.order_filter"
        arguments: [ { "id": ASC, "name": DESC } ]

    [...]
```

### Using a custom order query parameter name

A conflict will occur if `order` is also the name of a property with the search filter enabled.
Hopefully, the query parameter name to use is configurable:

```yaml

# app/config/config.yml

dunglas_api:
    collection:
        filter_name:
            order:   "_order" # the URL query parameter to use is now "_order"
```

## Enabling a filter for all properties of a resource

As we seen in previous examples, properties where filters can be applied must be
explicitly declared. But if you don't care about security and performances (ex:
an API with restricted access), it's also possible to enable builtin filters for
all properties:

```yaml

# app/config/services.yml

services:
    # Filter enabled for all properties
    resource.offer.order_filter:
        parent:    "api.doctrine.orm.order_filter"
        arguments: [ ~ ] # This line can also be omitted
```

Regardless of this option, filters can by applied on a property only if:
- the property exists
- the value is supported (ex: `asc` or `desc` for the order filters).

It means that the filter will be **silently** ignored if the property:
- does not exist
- is not enabled
- has an invalid value


## Creating custom filters

Custom filters can be written by implementing the `Dunglas\ApiBundle\Api\Filter\FilterInterface`
interface.

Don't forget to register your custom filters with the `Dunglas\ApiBundle\Api\Resource::initFilters()` method.

If you use [custom data providers](data-providers.md), they must support filtering and be aware of actives filters to
work properly.

### Creating custom Doctrine ORM filters

Doctrine ORM filters must implement the `Dunglas\ApiBundle\Doctrine\Orm\FilterInterface`.
They can interact directly with the Doctrine `QueryBuilder`.

A convenient abstract class is also shipped with the bundle: `Dunglas\ApiBundle\Doctrine\Orm\AbstractFilter`

### Overriding extraction of properties from the request

How filters data are extracted from the request can be changed for all built-in
filters by extending the parent filter class an overriding the `extractProperties(\Symfony\Component\HttpFoundation\Request $request)`
method.

In the following example, we will completely change the syntax of the order filter
to be the following: `?filter[order][property]`

```php

// src/AppBundle/Filter/CustomOrderFilter.php

namespace AppBundle\Filter;

use Dunglas\ApiBundle\Doctrine\Orm\OrderFilter;
use Symfony\Component\HttpFoundation\Request;

class CustomOrderFilter extends OrderFilter
{
    protected function extractProperties(Request $request)
    {
        $filter = $request->query->get('filter[order]', []);
    }
}
```

Finally, register the custom filter:

```yaml

# app/config/services.yml

services:
    resource.offer.custom_order_filter:
        class:    "AppBundle\Filter\CustomOrderFilter"

    resource.offer:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Offer" ]
        calls:
            -      method:    "initFilters"
                   arguments: [ [ "@resource.offer.custom_order_filter" ] ]
        tags:      [ { name: "api.resource" } ]
```

Beware: in [some cases](https://github.com/dunglas/DunglasApiBundle/issues/157#issuecomment-119576010) you may have to use double slashes in the class path to make it work:

```
services:
    resource.offer.custom_order_filter:
        class:    "AppBundle\\Filter\\CustomOrderFilter"
```

Previous chapter: [Data providers](data-providers.md)<br>
Next chapter: [Serialization groups and relations](serialization-groups-and-relations.md)
