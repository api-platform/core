# DunglasJsonLdApiBundle
**JSON-LD + Hydra REST API generator for Symfony**

This a work in progress under active development.
This bundle relies heavily on the Serializer of Symfony 2.7 and *is not usable in production yet*.

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
        class:     "Dunglas\JsonLdApiBundle\JsonLd\Resource"
        arguments: [ "AppBundle\Entity\Product" ]
        tags:      [ { name: "json-ld.resource" } ]

    resource.offer:
        class:     "Dunglas\JsonLdApiBundle\JsonLd\Resource"
        arguments: [ "AppBundle\Entity\Offer" ]
        tags:      [ { name: "json-ld.resource" } ]
```

**You're done!**

You now have a fully featured API exposing your Doctrine entities.
Run the Symfony app (`app/console server:run`) and browse the API entrypoint at `http://localhost:8000/api`.

Interact with it using a REST client such as [Postman](https://chrome.google.com/webstore/detail/postman-rest-client/fdmmgilgnpjigdojojpjoooidkmcomcm)
and take a look at the usage examples in [the `features` directory](features/).

Note : [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle) (dev-master) as built-in support for this bundle.
Installing it will give you access to a human-readable documentation and a nice sandbox.

## Advanced usage

### Filters

#### Fields

How to expose filters on collections? Just register them in your services definition.

To allow filtering the list of offers:

```yaml
services:
    resource.offer:
        class:     "Dunglas\JsonLdApiBundle\JsonLd\Resource"
        arguments:
            - "AppBundle\Entity\Offer"
            -
                - { "name": "price" }
                - { "name": "name", "exact": false }
        tags:      [ { name: "json-ld.resource" } ]
```

`http://localhost:8000/api/offers?price=10` will return all offers with a price being exactly of `10`.
`http://localhost:8000/api/offers?name=shirt` will returns all offer with a description containing the word "shirt".

#### Relations

It also possible to filter by relations:

```yaml
services:
    resource.offer:
        class:     "Dunglas\JsonLdApiBundle\JsonLd\Resource"
        arguments:
            - "AppBundle\Entity\Offer"
            -
                - { "name": "product" }
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
        class:     "Dunglas\JsonLdApiBundle\JsonLd\Resource"
        arguments: [ "AppBundle\Entity\Product", [], [ "serialization_group1", "serialization_group2" ], [ "deserialization_group1", "deserialization_group2" ] ]
        tags:      [ { name: "json-ld.resource" } ]
```

The built-in controller and the Hydra documentation generator will leverage specified serialization and deserialization
to give access only to exposed properties and to guess if they are readable or/and writable.

### Embedding relations

By default, the serializer provided with the bundle will represent relations between objects by a dereferenceable URI allowing
to retrieve details of the related object by issuing another HTTP request.

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

From a performance point of view, it's sometimes necessary to embed the related object (of a part of it) directly in the
parent response.

The bundle allows that trough serialization groups. Using the following serizalization groups annotations (`@Groups`) and
this updated service definition, a JSON representation of the product will be embedded in the offer response.

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
        class:     "Dunglas\JsonLdApiBundle\JsonLd\Resource"
        arguments: [ "AppBundle\Entity\Offer", [], { groups: [ "offer" ] } ]
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

The built-in controller is able to leverage Symfony's [validation groups](http://symfony.com/doc/current/book/validation.html#validation-groups)?

To take care of them, edit your service declaration and add groups you want to use when the validation occurs:

```yaml
services:
    resource.product:
        class:     "Dunglas\JsonLdApiBundle\JsonLd\Resource"
        arguments: [ "AppBundle\Entity\Product", [], [], [], [ "group1", "group2" ] ]
        tags:      [ { name: "json-ld.resource" } ]
```

With the previous definition, the validations groups `group1` and `group2` will be used when the validation occurs.

### Events

The bundle provides a powerful event system triggered in the object lifecycle. Here is the list:

- `dunglas_json_ld_api.pre_create` (`Dunglas\JsonLdApiBundle\Event::PRE_CREATE`): occurs after the object validation and before its persistence during a `POST` request
- `dunglas_json_ld_api.post_create` (`Dunglas\JsonLdApiBundle\Event::POST_CREATE`): event occurs after the object persistence during `POST` request
- `dunglas_json_ld_api.pre_update` (`Dunglas\JsonLdApiBundle\Event::PRE_UPDATE`): occurs after the object validation and before its persistence during a `PUT` request
- `dunglas_json_ld_api.post_create` (`Dunglas\JsonLdApiBundle\Event::POST_UPDATE`): event occurs after the object persistence during a `PUT` request
- `dunglas_json_ld_api.pre_delete` (`Dunglas\JsonLdApiBundle\Event::PRE_DELETE`): event occurs before the object deletion during a `DELETE` request
- `dunglas_json_ld_api.pre_create` (`Dunglas\JsonLdApiBundle\Event::POST_DELETE`): occurs after the object deletion during a `DELETE` request

### Cache

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

### Using a custom `Resource` class

TODO

### Using a custom controller

TODO

## Resources

* [API-first et Linked Data avec Symfony](http://les-tilleuls.coop/slides/dunglas/slides-sfPot-2015-01-15/#/) (in french)

## Credits

This project has been created by [Kévin Dunglas](http://dunglas.fr).
Sponsored by [Les-Tilleuls.coop](http://les-tilleuls.coop).
