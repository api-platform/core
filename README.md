# DunglasJsonLdApiBundle
**JSON-LD + Hydra REST API generator for Symfony**

This a work in progress under active development.
This bundle relies heavily on the Serializer of Symfony 2.7 and *is not usable in production yet*.

[![JSON-LD enabled](http://json-ld.org/images/json-ld-button-88.png)](http://json-ld.org)
[![Build Status](https://travis-ci.org/dunglas/DunglasJsonLdApiBundle.svg)](https://travis-ci.org/dunglas/DunglasJsonLdApiBundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a93f5a40-483f-4c46-ba09-3e1033b62552/mini.png)](https://insight.sensiolabs.com/projects/a93f5a40-483f-4c46-ba09-3e1033b62552)

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

Everything is fully customizable trough a powerful event system and strong OOP.
This bundle is documented and tested with Behat (take a look at [the `features/` directory](features/)).
 
## Installation

If you are starting a new project, the easiest way to get this bundle working and well integrated with other useful tools
such as PHP Schema, NelmioApiDocBundle, NelmioCorsBundle or Behat is to install [Dunglas's API Platform](https://github.com/dunglas/api-platform).
It's a Symfony edition packaged with the best tools to develop a REST API and with sensitive settings.

Alternatively, you can use [Composer](http://getcomposer.org) to install the standalone bundle in your project:

`composer require dunglas/json-ld-api-bundle`

Then, update your `app/config/AppKernel.php` file:

```php
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Dunglas\JsonLdApiBundle\DunglasJsonLdApiBundle(),
            // ...
        );

        return $bundles;
    }
```

Register the routes of our API by adding the following lines to `app/config/routing.yml`:

```yaml
api_doc:
    resource: "@DunglasJsonLdApiBundle/Resources/config/routing.xml"
    prefix:   "/api" # Optional

api:
    resource: "."
    type:     "json-ld"
    prefix:   "/api" # Optional
```

## Usage

### Configure

The first step is to name your API. Add the following lines in `app/config/config.yml`:

```yaml
dunglas_json_ld_api:
    title:       "Your API name"
    description: "The full description of your API"
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
        parent:    "dunglas_json_ld_api.resource"
        arguments: [ "AppBundle\Entity\Product" ]
        tags:      [ { name: "json-ld.resource" } ]

    resource.offer:
        parent:    "dunglas_json_ld_api.resource"
        arguments: [ "AppBundle\Entity\Offer" ]
        tags:      [ { name: "json-ld.resource" } ]
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

#### Fields

How to expose filters on collections? Just register them in your services definition.

To allow filtering the list of offers:

```yaml
services:
    resource.offer:
        parent:    "dunglas_json_ld_api.resource"
        arguments: [ "AppBundle\Entity\Offer" ]
        calls:     [ [ "initFilters", [ [ { "name": "price" }, { "name": "name", "exact": false } ] ] ] ]
        tags:      [ { name: "json-ld.resource" } ]
```

`http://localhost:8000/api/offers?price=10` will return all offers with a price being exactly `10`.
`http://localhost:8000/api/offers?name=shirt` will returns all offer with a description containing the word "shirt".

#### Relations

It also possible to filter by relations:

```yaml
services:
    resource.offer:
        parent:    "dunglas_json_ld_api.resource"
        arguments: [ "AppBundle\Entity\Offer"] 
        calls:     [ [ "initFilters", [ [ { "name": "product" } ] ] ] ]
        tags:      [ { name: "json-ld.resource" } ]
```

With this service definition, it is possible to find all offers for the given product.
Try the following: `http://localhost:8000/api/offers?product=/api/products/1`

It will return all offers for the product having the JSON-LD identifier (`@id`) `http://localhost:8000/api/products/1`.


### Serialization groups

Symfony 2.7 introduced [serialization (and deserialization) groups support](http://symfony.com/blog/new-in-symfony-2-7-serialization-groups)
in the Serializer component. Specifying to the API system the groups to use is damn easy:

```yaml
services:
    resource.product:
        parent:    "dunglas_json_ld_api.resource"
        arguments: [ "AppBundle\Entity\Product" ]
        calls:
            -      [ "initNormalizationContext", [ { groups: [ "serialization_group1", "serialization_group2" ] } ] ]
            -      [ "initDeormalizationContext", [ { groups: [ "deserialization_group1", "deserialization_group2" ] } ] ]
        tags:      [ { name: "json-ld.resource" } ]
```

The built-in controller and the Hydra documentation generator will leverage specified serialization and deserialization
to give access only to exposed properties and to guess if they are readable or/and writable.

### Embedding relations

By default, the serializer provided with DunglasJsonLdApiBundle will represent relations between objects by a dereferenceables
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
        parent:    "dunglas_json_ld_api.resource"
        arguments: [ "AppBundle\Entity\Offer" ]
        calls:     [ [ "initNormalizationContext", [ [ { groups: [ "offer" ] } ] ] ] ]
        tags:      [ { name: "json-ld.resource" } ]
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

### Validation groups

The built-in controller is able to leverage Symfony's [validation groups](http://symfony.com/doc/current/book/validation.html#validation-groups).

To take care of them, edit your service declaration and add groups you want to use when the validation occurs:

```yaml
services:
    resource.product:
        parent:    "dunglas_json_ld_api.resource"
        arguments: [ "AppBundle\Entity\Product" ]
        calls:     [ [ "initValidationGroups", [ [ "group1", "group2" ] ] ] ]
        tags:      [ { name: "json-ld.resource" } ]
```

With the previous definition, the validations groups `group1` and `group2` will be used when the validation occurs.

### Events

The bundle provides a powerful event system triggered in the object lifecycle. Here is the list:

#### Retrieve lists

- `dunglas_json_ld_api.retrieve_list` (`Dunglas\JsonLdApiBundle\Event::RETRIEVE_LIST`): occurs after the retrieving of an object list during a `GET` request on a collection.

#### Retrieve item

- `dunglas_json_ld_api.retrieve` (`Dunglas\JsonLdApiBundle\Event::RETRIEVE_LIST`): after the retrieving of an object during a `GET` request on an item.

#### Create item

- `dunglas_json_ld_api.pre_create_validation` (`Dunglas\JsonLdApiBundle\Event::PRE_CREATE_VALIDATION`): occurs before the object validation during a `POST` request.
- `dunglas_json_ld_api.pre_create` (`Dunglas\JsonLdApiBundle\Event::PRE_CREATE`): occurs after the object validation and before its persistence during a `POST` request
- `dunglas_json_ld_api.post_create` (`Dunglas\JsonLdApiBundle\Event::POST_CREATE`): event occurs after the object persistence during `POST` request

#### Update item

- `dunglas_json_ld_api.pre_update_validation` (`Dunglas\JsonLdApiBundle\Event::PRE_UPDATE_VALIDATION`): event occurs before the object validation during a `PUT` request.
- `dunglas_json_ld_api.pre_update` (`Dunglas\JsonLdApiBundle\Event::PRE_UPDATE`): occurs after the object validation and before its persistence during a `PUT` request
- `dunglas_json_ld_api.post_update` (`Dunglas\JsonLdApiBundle\Event::POST_UPDATE`): event occurs after the object persistence during a `PUT` request

#### Delete item

- `dunglas_json_ld_api.pre_delete` (`Dunglas\JsonLdApiBundle\Event::PRE_DELETE`): event occurs before the object deletion during a `DELETE` request
- `dunglas_json_ld_api.post_delete` (`Dunglas\JsonLdApiBundle\Event::POST_DELETE`): occurs after the object deletion during a `DELETE` request

### Metadata cache

Computing metadata used by the bundle is a costly operation. Fortunately, metadata can be computed once then cached. The
bundle provides a built-in cache service using [APCu](https://github.com/krakjoe/apcu).
To enable it in the prod environment (requires APCu to be installed), add the following lines to `app/config/config_prod.yml`:

```yaml
dunglas_json_ld_api:
    cache: dunglas_json_ld_api.mapping.cache.apc
```

DunglasJsonLdApiBundle leverages [Doctrine Cache](https://github.com/doctrine/cache) to abstract the cache backend. If
you want to use a custom cache backend such as Redis, Memcache or MongoDB, register a Doctrine Cache provider as a service
and set the `cache` config key to the id of the custom service you created.

A built-in cache warmer will be automatically executed every time you clear or warmup the cache if a cache service is configured.

### Disabling operations

By default, the following operations are automatically enabled:

*Collection*

| Method | Meaning                                   |
|--------|-------------------------------------------|
| `GET`  | Retrieve the (paginated) list of elements |
| `POST` | Create a new element                      |

*Item*

| Method   | Meaning                                   |
|----------|-------------------------------------------|
| `GET`    | Retrieve element (mandatory operation)    |
| `PUT`    | Update an element                         |
| `DELETE` | Delete an element                         |

Sometimes, you want to disable some operations (e.g. the `DELETE` operation). `initCollectionOperations` and `initItemOperations`
of the `Resource` class respectively allow to customize operations available for the collection and for items of the given
resource.

The following `Resource` definition exposes a `GET` operation for it's collection but not the `POST` one:

```yaml
services:
    resource.product:
        parent:    "dunglas_json_ld_api.resource"
        arguments: [ "AppBundle\Entity\Product" ]
        calls:     [ [ "initCollectionOperations", [ [ { "hydra:method": "GET" } ] ] ] ]
        tags:      [ { name: "json-ld.resource" } ]
```

### Defining custom operations

Sometimes, it can be useful to create custom controller actions. DunglasJsonLdApiBundle allows to register custom operations
for both collections and items. It will register them automatically in the Symfony routing system and will expose them in
the Hydra vocab.

```yaml
    my_relation_embedder_resource:
        parent:    "dunglas_json_ld_api.resource"
        arguments: [ "AppBundle\Entity\Product" ]
        calls:     [ [ "initItemOperations", [ [
                       { "hydra:method": "GET" },
                       { "hydra:method": "PUT" }
                       { "hydra:method": "GET", "@type": "hydra:Operation", "hydra:title": "A custom operation", "!controller": "AppBundle:Custom:custom", "!route_name": "my_custom_route", "!route_path": "/my_entities/{id}/custom", "returns": "xmls:string" }
                   ] ] ] ]
```

Additionnaly to the default generated `GET` and `PUT` operations, this definition will expose a new `GET` operation for
the `/my_entities/{id}/custom` URL. When this URL is opened, the `AppBundle:Custom:custom` controller is called.

### Using a custom `Resource` class

When the size of your services definition start to grow, or when you want to customize the behavior of the `Resource` class
it can be useful to extend the default one.

```php
<?php

namespace AppBundle\JsonLd;

use Dunglas\JsonLdApiBundle\JsonLd\Resource;

class MyCustomResource extends Resource
{
    public function __construct(
        $entityClass = 'AppBundle\Entity\Offer',
        array $filters = ['name' => 'price', 'exact' => true],
        array $normalizationContext = ['groups' => ['offers']],
        array $denormalizationContext = ['groups' => ['offers']],,
        array $validationGroups = null,
        $shortName = null,
        array $collectionOperations = ['hydra:method' => 'GET'],
        array $itemOperations = ['hydra:method' => 'GET', 'hydra:method' => 'PUT'],
        $controllerName = 'AppBundle:Controller:Custom'
    ) {
        parent::__construct($entityClass, $filters, $normalizationContext, $denormalizationContext, $validationGroups, $shortName, $collectionOperations, $itemOperations, $controllerName);
    }
}
```

The service definition can now be simplified:

```yaml
services:
    resource.product:
        parent:    "dunglas_json_ld_api.resource"
        class:     "AppBundle\JsonLd\MyCustomResource"
        tags:      [ { name: "json-ld.resource" } ]
```

### Using a custom controller

If you want to customize the controller used for a `Resource` pass the controller name as its last constructor parameter:

```yaml
services:
    resource.product:
        parent:    "dunglas_json_ld_api.resource"
        arguments: [ "AppBundle\Entity\Product" ]
        calls:     [ [ "initControllerName", [ "AppBundle:Custom" ] ] ]
        tags:      [ { name: "json-ld.resource" } ]
```

Your custom controller should extend the `ResourceController` provided by this bundle. It provides convenient methods to
retrieve the `Resource` class associated with the current request and to serialize entities in JSON-LD.

Example of custom controller:

```php
<?php

namespace AppBundle\Controller;

use Dunglas\JsonLdApiBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\Request;

class CustomController extends ResourceController
{
    # Customize the AppBundle:Custom:get
    public function getAction(Request $request, $id)
    {
        $this->get('logger')->info('This is my custom controller.');
        
        return parent::getAction($request, $id);
    }
}
```

### AngularJS integration

DunglasJsonLdApiBundle works fine with [AngularJS](http://angularjs.org). The popular [Restangular](https://github.com/mgonto/restangular)
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

* [API-first et Linked Data avec Symfony](http://les-tilleuls.coop/slides/dunglas/slides-sfPot-2015-01-15/#/) (in french)

## Credits

This project has been created by [Kévin Dunglas](http://dunglas.fr).
Sponsored by [Les-Tilleuls.coop](http://les-tilleuls.coop).
