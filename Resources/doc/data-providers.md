# Data providers

To retrieve data that will be exposed by the API, DunglasApiBundle use classes called data providers. A data provider
using [Doctrine ORM](http://www.doctrine-project.org/projects/orm.html) to retrieve data from a database is included with the bundle and enabled by default. This data provider
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
        return 'AppBundle\Entity\MyEntity' === $resource->getEntityClass();
    }
}
```

Then register that provider with a priority higher than the Doctrine ORM data provider:

```yaml

# app/config/services.yml

services:
    my_custom_data_provider:
        class: AppBundle\DataProvider\StaticDataProvider
        tags:  [ { name: "api.data_provider", priority: 1 } ]
```

This data provider is now up and running. It will take precedence over the default Doctrine ORM data provider for each resources
it supports (here, resources managing `AppBundle\Entity\MyEntity` entities).

## Returning a paged collection

The previous custom data provider return only full, non paged, collection. However for large collections, returning all
the data set in one response is often not possible.
The `getCollection()` method of data providers supporting paged collections must returns an instance of `Dunglas\ApiBundle\Model\PaginatorInterface`
instead of a standard array.

To create your own paginators, take a look at the Doctrine ORM paginator bridge: [`Dunglas\ApiBundle\Doctrine\Orm\Paginator`](/Doctrine/Orm/Paginator.php).

## Supporting filters

To be able [to filter collections](filters.md), the Data Provider must be aware of registered filters to the given resource.
The best way to learn how to create filter aware data provider is too look at the default Doctrine ORM dataprovider: [`Dunglas\ApiBundle\Doctrine\Orm\DataProvider`](/Doctrine/Orm/DataProvider.php).

## Extending the Doctrine Data Provider

The bundle is provided with a data provider leveraging the Doctrine ORM. This default data provider can be extended.

For performance reasons, [custom output walkers for the Doctrine ORM Paginator](http://www.doctrine-project.org/jira/browse/DDC-3282)
are disabled. It drastically improves performance when dealing with large collections. However it prevents advanced [filters](filters.md)
adding `HAVING` and `GROUP BY` clauses to DQL queries to work properly.

To enable custom output walkers, start by creating a custom data provider supporting the `AppBundle\Entity\MyEntity` class:

```php
<?php

// src/AppBundle/DataProvider/MyEntityDataProvider.php

namespace AppBundle\DataProvider;

use Dunglas\ApiBundle\Doctrine\Orm\DataProvider;
use Dunglas\ApiBundle\Model\DataProviderInterface;

class MyEntityDataProvider extends DataProvider
{
    protected function getPaginator(QueryBuilder $queryBuilder)
    {
        $doctrineOrmPaginator = new DoctrineOrmPaginator($queryBuilder);
        // Enable output walkers to make queries with HAVING and ORDER BY clauses working
        $doctrineOrmPaginator->setUseOutputWalkers(true);

        return new Paginator($doctrineOrmPaginator);
    }
    
    public function supports(ResourceInterface $resource)
    {
        return 'AppBundle\Entity\MyEntity' === $resource->getEntityClass();
    }
}
```

Then register the data provider:

```yaml

# app/config/services.yml

services:
    my_entity_data_provider:
        parent: "api.doctrine.orm.data_provider"
        class: AppBundle\DataProvider\MyEntityDataProvider
        tags:  [ { name: "api.data_provider", priority: 1 } ]
```

Previous chapter: [Operations](operations.md)<br>
Next chapter: [Filters](filters.md)
