# DunglasApiBundle
**JSON-LD + Hydra REST API system for Symfony**

This a work in progress under active development.
This bundle relies heavily on the Serializer of Symfony 2.7 and *is not usable in production yet*.

[![JSON-LD enabled](http://json-ld.org/images/json-ld-button-88.png)](http://json-ld.org)
[![Build Status](https://travis-ci.org/dunglas/DunglasApiBundle.svg)](https://travis-ci.org/dunglas/DunglasApiBundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a93f5a40-483f-4c46-ba09-3e1033b62552/mini.png)](https://insight.sensiolabs.com/projects/a93f5a40-483f-4c46-ba09-3e1033b62552)
[![HHVM Status](http://hhvm.h4cc.de/badge/dunglas/api-bundle.svg)](http://hhvm.h4cc.de/package/dunglas/api-bundle)

## Features

Here is the fully-featured REST API you'll get in minutes, I promise:

* CRUD support through the API for Doctrine entities: list, `GET`, `POST`, `PUT` and `DELETE`
* Hypermedia implementing [JSON-LD](http://json-ld.org)
* Machine-readable documentation in [Hydra](http://hydra-cg.com), guessed from PHPDoc, Serializer, Validator and Doctrine ORM metadata
* Pagination (following the Hydra format)
* List filters (following the Hydra format)
* Validation (through the Symfony Validator Component, supporting groups)
* Errors serialization (following the Hydra format)
* Custom serialization (through the Symfony Serializer Component, supporting groups)
* Automatic routes registration
* Automatic entrypoint generation giving access to all resources
* `\DateTime` serialization and deserialization
* [FOSUserBundle](https://github.com/FriendsOfSymfony/FOSUserBundle) integration

Everything is fully customizable through a powerful event system and strong OOP.
This bundle is documented and tested with Behat (take a look at [the `features/` directory](features/)).
 
## Installation

If you are starting a new project, the easiest way to get this bundle working and well integrated with other useful tools
such as PHP Schema, NelmioApiDocBundle, NelmioCorsBundle or Behat is to install [Dunglas's API Platform](https://github.com/dunglas/api-platform).
It's a Symfony edition packaged with the best tools to develop a REST API and with sensitive settings.

Alternatively, you can use [Composer](http://getcomposer.org) to install the standalone bundle in your project:

`composer require dunglas/api-bundle`

Then, update your `app/config/AppKernel.php` file:

```php
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Dunglas\ApiBundle\DunglasApiBundle(),
            // ...
        ];

        return $bundles;
    }
```

Register the routes of our API by adding the following lines to `app/config/routing.yml`:

```yaml
api:
    resource: "."
    type:     "api"
    prefix:   "/api" # Optional
```

## Usage

### Configure

The first step is to name your API. Add the following lines in `app/config/config.yml`:

```yaml
dunglas_api:
    title:              "Your API name"
    description:        "The full description of your API"
    default:                                               # optional
        items_per_page: 30                                 # Number of items per page in paginated collections (optional)
        order:          ~                                  # Default order: null for natural order, ASC or DESC (optional)
```

The name and the description you give will be accessible trough the auto-generated Hydra documentation.

### Map your entities

Imagine you have the following Doctrine entity classes:

```php
<?php

# src/AppBundle/Entity/Product.php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 */
class Product
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @ORM\Column
     * @Assert\NotBlank
     */
    public $name;
}
```

```php
<?php

# src/AppBundle/Entity/Offer.php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 */
class Offer
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @ORM\Column(type="text")
     */
    public $description;
    /**
     * @ORM\Column(type="float")
     * @Assert\NotBlank
     * @Assert\Range(min=0, message="The price must be superior to 0.")
     * @Assert\Type(type="float")
     */
    public $price;
    /**
     * @ORM\ManyToOne(targetEntity="Product")
     */
    public $product;
}
```

Register the following services (for example in `app/config/services.yml`):

```yaml
services:
    resource.product:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Product" ]
        tags:      [ { name: "api.resource" } ]

    resource.offer:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Offer" ]
        tags:      [ { name: "api.resource" } ]
```

**You're done!**

You now have a fully featured API exposing your Doctrine entities.
Run the Symfony app (`app/console server:run`) and browse the API entrypoint at `http://localhost:8000/api`.

Interact with it using a REST client such as [Postman](https://chrome.google.com/webstore/detail/postman-rest-client/fdmmgilgnpjigdojojpjoooidkmcomcm)
and take a look at the usage examples in [the `features` directory](features/).

Note: [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle) (dev-master) has built-in support for this bundle.
Installing it will give you access to a human-readable documentation and a nice sandbox.

## Advanced usage

### Filters

The bundle provides a generic system to apply filters on collections. It ships with built-in Doctrine ORM support
and can be extended to fit your specific needs.

By default, all filters are disabled. They must be enabled manually.

#### Doctrine ORM filters

If Doctrine ORM support is enabled, adding filters is as easy as adding an entry in your `app/config/services.yml` file.
It supports exact and partial matching strategies. If the partial strategy is specified, a SQL query with a `LIKE %text to search%`
query will be automatically issued.

To allow filtering the list of offers:

```yaml
services:
    resource.offer.filter.id:
        parent:    "api.doctrine.orm.filter"
        arguments: [ "id" ] # Filters on the id property, allow both numeric values and IRIs

    resource.offer.filter.price:
        parent:    "api.doctrine.orm.filter"
        arguments: [ "price" ] # Extracts all collection elements with the exact given price

    resource.offer.filter.name:
        parent:    "api.doctrine.orm.filter"
        arguments: [ "name", "partial" ] # Elements with given text in their name

    resource.offer:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Offer" ]
        calls:
            -      method:    "addFilter"
                   arguments:
                       -      "@resource.offer.filter.id"
            -      method:    "addFilter"
                   arguments:
                       -      "@resource.offer.filter.price"
            -      method:    "addFilter"
                   arguments:
                       -      "@resource.offer.filter.name"
        tags:      [ { name: "api.resource" } ]
```

`http://localhost:8000/api/offers?price=10` will return all offers with a price being exactly `10`.
`http://localhost:8000/api/offers?name=shirt` will returns all offer with a description containing the word "shirt".

Filters can be combined together: `http://localhost:8000/api/offers?price=10&name=shirt`

It also possible to filter by relations:

```yaml
services:
    resource.offer.filter.product:
        parent:    "api.doctrine.orm.filter"
        arguments: [ "product" ]

    resource.offer:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Offer"] 
        calls:
            -      method:    "addFilter"
                   arguments:
                       -      "@resource.offer.filter.product"
        tags:      [ { name: "api.resource" } ]
```

With this service definition, it is possible to find all offers for the given product.
Try the following: `http://localhost:8000/api/offers?product=/api/products/12`
Using a numeric ID will also work: `http://localhost:8000/api/offers?product=12`

It will return all offers for the product having the JSON-LD identifier (`@id`) `http://localhost:8000/api/products/12`.

#### Creating custom filters

Custom filters can be written by implementing the `Dunglas\ApiBundle\Api\Filter\FilterInterface` interface.
Doctrine ORM filters must implement the `Dunglas\ApiBundle\Doctrine\Orm\FilterInterface`. They can interact directly
with the Doctrine `QueryBuilder`.

Don't forget to register your custom filters with the `Dunglas\ApiBundle\Api\Resource::addFilter()` method.

### Serialization groups

Symfony 2.7 introduced [serialization (and deserialization) groups support](http://symfony.com/blog/new-in-symfony-2-7-serialization-groups)
in the Serializer component. Specifying to the API system the groups to use is damn easy:

```yaml
services:
    resource.product:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Product" ]
        calls:
            -      method:    "initNormalizationContext"
                   arguments:
                       -      { groups: [ "serialization_group1", "serialization_group2" ] }
            -      method:    "initDenormalizationContext"
                   arguments:
                       -      { groups: [ "deserialization_group1", "deserialization_group2" ] }
        tags:      [ { name: "api.resource" } ]
```

The built-in controller and the Hydra documentation generator will leverage specified serialization and deserialization
to give access only to exposed properties and to guess if they are readable or/and writable.

### Embedding relations

By default, the serializer provided with DunglasApiBundle will represent relations between objects by dereferenceables
URIs. They allow to retrieve details of related objects by issuing an extra HTTP request.

In the following JSON document, the relation from an offer to a product is represented by an URI:

```json
{
  "@context": "/contexts/Offer",
  "@id": "/offer/62",
  "@type": "Offer",
  "price": 31.2,
  "product": "/products/59"
}
```

#### Normalization

From a performance point of view, it's sometimes necessary to avoid extra HTTP requests. It is possible to embed related
objects (or only some of their properties) directly in the parent response trough serialization groups.
By using the following serizalization groups annotations (`@Groups`) and this updated service definition, a JSON representation
of the product is embedded in the offer response:

```php
<?php

# src/AppBundle/Entity/Offer.php

namespace AppBundle\Entity;

use Symfony\Component\Serializer\Annotation\Groups;

// ...

class Offer
{
    // ...
    
    /**
     * ...
     * @Groups({"offer"})
     */
    public $price;
    /**
     * ...
     * @Groups({"offer"})
     */
    public $product;
}
```

```php
<?php

# src/AppBundle/Entity/Product.php

namespace AppBundle\Entity;

use Symfony\Component\Serializer\Annotation\Groups;

// ...

class Product
{
    // ...

    /**
     * ...
     * @Groups({"offer"})
     */
    public $name;
}
```

Register the following services (for example in `app/config/services.yml`):

```yaml
services:
    # ...

    resource.offer:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Offer" ]
        calls:
            -      method:    "initNormalizationContext"
                   arguments:
                       -      { groups: [ "offer" ] }
        tags:      [ { name: "api.resource" } ]
```

The generated JSON with previous settings will be like the following:

```json
{
  "@context": "/contexts/Offer",
  "@id": "/offer/62",
  "@type": "Offer",
  "price": 31.2,
  "product": {
    "@id": "/products/59",
    "@type": "Product",
    "name": "Lyle and Scott polo skirt"
  }
}
```

#### Denormalization

It is also possible to embed a relation in `PUT` and `POST` requests. To enable that feature, serialization groups must be
set the same way as normalization and the service definition must be like the following:

```yaml
services:
    # ...

    resource.offer:
        parent:     "api.resource"
        arguments:  [ "AppBundle\Entity\Offer" ]
        calls:
            -       method:    "initDenormalizationContext"
                    arguments:
                        -      { groups: [ "offer" ] }
        tags:       [ { name: "api.resource" } ]
```

The following rules apply when denormalizating embedded relations:
* if a `@id` key is present in the embedded resource, the object corresponding to the given URI will be retrieved trough
the data provider and any changes in the embedded relation will be applied to that object.
* if no `@id` key exists, a new object will be created containing data provided in the embedded JSON document.

You can create as relation embedding levels as you want.

### Validation groups

The built-in controller is able to leverage Symfony's [validation groups](http://symfony.com/doc/current/book/validation.html#validation-groups).

To take care of them, edit your service declaration and add groups you want to use when the validation occurs:

```yaml
services:
    resource.product:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Product" ]
        calls:
            -      method:    "initValidationGroups"
                   arguments:
                       -      [ "group1", "group2" ]
        tags:      [ { name: "api.resource" } ]
```

With the previous definition, the validations groups `group1` and `group2` will be used when the validation occurs.

### Events

The bundle provides a powerful event system triggered in the object lifecycle. Here is the list:

#### Retrieve lists

- `api.retrieve_list` (`Dunglas\ApiBundle\Event::RETRIEVE_LIST`): occurs after the retrieving of an object list during a `GET` request on a collection.

#### Retrieve item

- `api.retrieve` (`Dunglas\ApiBundle\Event::RETRIEVE_LIST`): after the retrieving of an object during a `GET` request on an item.

#### Create item

- `api.pre_create_validation` (`Dunglas\ApiBundle\Event::PRE_CREATE_VALIDATION`): occurs before the object validation during a `POST` request.
- `api.pre_create` (`Dunglas\ApiBundle\Event::PRE_CREATE`): occurs after the object validation and before its persistence during a `POST` request
- `api.post_create` (`Dunglas\ApiBundle\Event::POST_CREATE`): event occurs after the object persistence during `POST` request

#### Update item

- `api.pre_update_validation` (`Dunglas\ApiBundle\Event::PRE_UPDATE_VALIDATION`): event occurs before the object validation during a `PUT` request.
- `api.pre_update` (`Dunglas\ApiBundle\Event::PRE_UPDATE`): occurs after the object validation and before its persistence during a `PUT` request
- `api.post_update` (`Dunglas\ApiBundle\Event::POST_UPDATE`): event occurs after the object persistence during a `PUT` request

#### Delete item

- `api.pre_delete` (`Dunglas\ApiBundle\Event::PRE_DELETE`): event occurs before the object deletion during a `DELETE` request
- `api.post_delete` (`Dunglas\ApiBundle\Event::POST_DELETE`): occurs after the object deletion during a `DELETE` request

### Metadata cache

Computing metadata used by the bundle is a costly operation. Fortunately, metadata can be computed once then cached. The
bundle provides a built-in cache service using [APCu](https://github.com/krakjoe/apcu).
To enable it in the prod environment (requires APCu to be installed), add the following lines to `app/config/config_prod.yml`:

```yaml
dunglas_json_ld_api:
    cache: api.mapping.cache.apc
```

DunglasApiBundle leverages [Doctrine Cache](https://github.com/doctrine/cache) to abstract the cache backend. If
you want to use a custom cache backend such as Redis, Memcache or MongoDB, register a Doctrine Cache provider as a service
and set the `cache` config key to the id of the custom service you created.

A built-in cache warmer will be automatically executed every time you clear or warmup the cache if a cache service is configured.

### Using external JSON-LD vocabularies

JSON-LD allows to define classes and properties of your API with open vocabularies such as [Schema.org](https://schema.org)
and [Good Relations](http://www.heppnetz.de/projects/goodrelations/).

DunglasApiBundle provides annotations usable on PHP classes and properties to specify a related external [IRI](http://en.wikipedia.org/wiki/Internationalized_resource_identifier).


```php
<?php

# src/AppBundle/Entity/Product.php

namespace AppBundle\Entity;

use Dunglas\ApiBundle\Annotation\Iri;

// ...

/**
 * ...
 * @Iri("https://schema.org/Product")
 */
class Product
{
    // ...

    /**
     * ...
     * @Iri("https://schema.org/name")
     */
    public $name;
}
```

The generated JSON for products and the related context document will now use external IRIs according to the specified annotations:

`GET /products/22`

```json
{
  "@context": "/contexts/Product",
  "@id": "/product/22",
  "@type": "https://schema.org/Product",
  "name": "My awesome product",
  // other properties
}
```

`GET /contexts/Product`

```json
{
    "@context": {
        "@vocab": "http://example.com/vocab#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "name": "https://schema.org/name",
        // Other properties
    }
}
```

An extended list of existing open vocabularies is available on [the Linked Open Vocabularies (LOV) database](http://lov.okfn.org/dataset/lov/).


### Operations

By default, the following operations are automatically enabled:

*Collection*

| Method | Description                               |
|--------|-------------------------------------------|
| `GET`  | Retrieve the (paginated) list of elements |
| `POST` | Create a new element                      |

*Item*

| Method   | Description                               |
|----------|-------------------------------------------|
| `GET`    | Retrieve element (mandatory operation)    |
| `PUT`    | Update an element                         |
| `DELETE` | Delete an element                         |


#### Disabling operations

If you want to disable some operations (e.g. the `DELETE` operation), you must register manually applicable operations using
the operation factory class, `Dunglas\ApiBundle\Resource::addCollectionOperation()` and `Dunglas\ApiBundle\Resource::addCollectionOperation()`
methods.

The following `Resource` definition exposes a `GET` operation for it's collection but not the `POST` one:

```yaml
services:
    resource.product.collection_operation.get:
        class:     "Dunglas\ApiBundle\Api\Operation\Operation"
        public:    false
        factory:   [ "@api.operation_factory", "createItemOperation" ]
        arguments: [ "@resource.product", "GET" ]

    resource.product:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Product" ]
            -      [ "addCollectionOperation", [ "@resource.product.collection_operation.get" ] ]
        tags:      [ { name: "api.resource" } ]
```

Sometimes, it can be useful to create custom controller actions. DunglasApiBundle allows to register custom operations
for both collections and items. It will register them automatically in the Symfony routing system and will expose them in
the Hydra vocab (if enabled).

```yaml
    resource.product.item_operation.get:
        class:     "Dunglas\ApiBundle\Api\Operation\Operation"
        public:    false
        factory:   [ "@api.operation_factory", "createItemOperation" ]
        arguments: [ "@resource.product", "GET" ]

    resource.product.item_operation.put:
        class:     "Dunglas\ApiBundle\Api\Operation\Operation"
        public:    false
        factory:   [ "@api.operation_factory", "createItemOperation" ]
        arguments: [ "@resource.product", "PUT" ]


    resource.product.item_operation.custom_get:
        class:   "Dunglas\ApiBundle\Api\Operation\Operation"
        public:  false
        factory: [ "@api.operation_factory", "createItemOperation" ]
        arguments:
            -    "@resource.product"               # Resource
            -    [ "GET", "HEAD" ]                 # Methods
            -    "/products/{id}/custom" # Path
            -    "AppBundle:Custom:custom"         # Controller
            -    "my_custom_route"                 # Route name
            -    # Context (will be present in Hydra documentation)
                 "@type":       "hydra:Operation"
                 "hydra:title": "A custom operation"
                 "returns":     "xmls:string"

    resource.product:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Product" ]
        calls:
            -      method:    "addItemOperation"
                   arguments:
                       -      "@resource.product.item_operation.get"
            -      method:    "addItemOperation"
                   arguments:
                       -      "@resource.product.item_operation.put"
            -      method:    "addItemOperation"
                   arguments:
                       -      "@resource.product.item_operation.custom_get"
        tags:      [ { name: "api.resource" } ]
```

Additionnaly to the default generated `GET` and `PUT` operations, this definition will expose a new `GET` operation for
the `/products/{id}/custom` URL. When this URL is opened, the `AppBundle:Custom:custom` controller is called.

### Using a custom `Resource` class

When the size of your services definition start to grow, it is useful to create custom resources instead of using the default
one. To do so, the `Dunglas\ApiBundle\Api\ResourceInterface` interface must be implemented.

```php
<?php

namespace AppBundle\Api;

use Dunglas\ApiBundle\Api\ResourceInterface;

class MyCustomResource implements ResourceInterface
{
    public function getEntityClass()
    {
        return 'AppBundle\Entity\MyCustomOne';
    }
    
    public function getItemOperations() {
        return [
            new MyItemOperation();
        ];
    }
    
    public function getCollectionOperations()
    {
        return [
            new MyCollectionOperation();
        ];
    }

    public function getFilters()
    {
        return [];
    }
    
    public function getNormalizationContext()
    {
        return [];
    }

    public function getNormalizationGroups()
    {
        return null;
    }
    
    public function getDenormalizationContext()
    {
        return [];
    }

    public function getDenormalizationGroups()
    {
        return null;
    }

    public function getValidationGroups()
    {
        return null;
    }

    public function getShortName()
    {
        return 'MyCustomOne';
    }
}
```

The service definition can now be simplified:

```yaml
services:
    resource.product:
        parent: "api.resource"
        class:  "AppBundle\Api\MyCustomResource"
        tags:   [ { name: "api.resource" } ]
```

### Using a custom controller

A seen in the Operations section, it's possible to use custom controllers.

Your custom controller should extend the `ResourceController` provided by this bundle. It provides convenient methods to
retrieve the `Resource` class associated with the current request and to serialize entities in JSON-LD.

Example of custom controller:

```php
<?php

namespace AppBundle\Controller;

use Dunglas\ApiBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\Request;

class CustomController extends ResourceController
{
    # Customize the AppBundle:Custom:custom
    public function getAction(Request $request, $id)
    {
        $this->get('logger')->info('This is my custom controller.');
        
        return parent::getAction($request, $id);
    }
}
```

### AngularJS integration

DunglasApiBundle works fine with [AngularJS](http://angularjs.org). The popular [Restangular](https://github.com/mgonto/restangular)
REST client library for Angular can easily be configured to handle the API format.

Here is a working Restangular config:

```javascript
'use strict';

var app =
angular.module('myAngularjsApp')
    .config(['RestangularProvider', function(RestangularProvider) {
        // The URL of the API endpoint
        RestangularProvider.setBaseUrl('http://localhost:8000');

        // JSON-LD @id support
        RestangularProvider.setRestangularFields({
            id: '@id'
        });
        RestangularProvider.setSelfLinkAbsoluteUrl(false);

        // Hydra collections support
        RestangularProvider.addResponseInterceptor(function(data, operation, what, url, response, deferred) {
            // Remove trailing slash to make Restangular working
            function populateHref(data) {
                if (data['@id']) {
                    data['href'] = data['@id'].substring(1);
                }
            }

            // Populate href property for the collection
            populateHref(data);

            if ('getList' === operation) {
                var collectionResponse = data['hydra:member'];
                collectionResponse['metadata'] = {};

                // Put metadata in a property of the collection
                angular.forEach(data, function(value, key) {
                    if ('hydra:member' !== key) {
                        collectionResponse.metadata[key] = value;
                    }
                });

                // Populate href property for all elements of the collection
                angular.forEach(collectionResponse, function(value, key) {
                    populateHref(value);
                });

                return collectionResponse;
            }

            return data;
        });
    }])
;
```

## Resources

* [A la découverte de API Platform (Symfony Paris Live 2015)](http://dunglas.fr/2015/04/mes-slides-du-symfony-live-2015-a-la-decouverte-de-api-platform/) (in french)
* [API-first et Linked Data avec Symfony (sfPot Lille 2015)](http://les-tilleuls.coop/slides/dunglas/slides-sfPot-2015-01-15/#/) (in french)

## Credits

This project has been created by [Kévin Dunglas](http://dunglas.fr).
Sponsored by [Les-Tilleuls.coop](http://les-tilleuls.coop).
