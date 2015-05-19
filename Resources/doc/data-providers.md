# Data providers

To retrieve data that will be exposed by the API, DunglasApiBundle use classes called data providers. A data provider
using Doctrine ORM to retrieve data from a database is included with the bundle and enabled by default. This data provider
natively supports paged collection and filters. It can be used as is and fits perfectly with common usages.

But sometime, you want to retrieve data from other sources such as a webservice, ElasticSearch, MongoDB or another ORM.
Custom data providers can be used to do so. A project can include as much data providers as it needs. The first able to
retrieve data for a given resource will be used.

## Creating a custom data provider

Data providers must return collection of items and specific items for a given resource when requested. In the following
example, we will create a custom provider returning data from a static list of object. Fell free to adapt it to match your
own needs.

Let's start with the data provider itself:

```php
<?php

// src/AppBundle/DataProvider/StaticDataProvider.php

namespace AppBundle\DataProvider;

use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Model\DataProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class StaticDataProvider implements DataProviderInterface
{
    private $data;

    public function __construct()
    {
        $this->data = [
            'a1' => new MyEntity('a1', 1871),
            'a2' => new MyEntity('a2', 1936),
        ];
    }

    public function getItem(ResourceInterface $resource, $id, $fetchData = false)
    {
        return isset($this->data[$id]) ? $this->data[$id] : null;
    }

    public function getCollection(ResourceInterface $resource, Request $request)
    {
        return $this->data;
    }

    public function supports(ResourceInterface $resource)
    {
        return 'AppBundle\Entity\MyEntity` === $resource->getEntityClass();
    }
}
```

Then register that provider with a priority higher than the Doctrine ORM data provider:

```yaml

# app/config/services.yml

services:
    my_custom_data_provider:
        class: AppBundle\DataProvider\StaticDataProvider
        tags:  [ { name: "api.data_provider", priority=1 } ]
```

This data provider is now up and running. It will take precedence over the default Doctrine ORM data provider for each resources
it supports (here, resources managing `AppBundle\Entity\MyEntity` entities).

## Returning a paged collection

The previous custom data provider return only full, non paged, collection. However for large collections, returning all
the data set in one response is often not possible.
The `getCollection()` method of data providers supporting paged collections must returns an instance of `Dunglas\ApiBundle\Model\PaginatorInterface`
instead of a standard array.

To create your own paginators, take a look at the Doctrine ORM paginator bridge: [`Dunglas\ApiBundle\Doctrine\Orm\Paginator`](src/Doctrine/Orm/Paginator.php).

## Supporting filters

To be able [to filter collections](filters.md), the Data Provider must be aware of registered filters to the given resource.
The best way to learn how to create filter aware data provider is too look at the default Doctrine ORM dataprovider: [`Dunglas\ApiBundle\Doctrine\Orm\DataProvider`](src/Doctrine/Orm/DataProvider.php).

Previous chapter: [Operations](operations.md)<br>
Next chapter: [Filters](filters.md)
