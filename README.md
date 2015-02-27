# DunglasJsonLdApiBundle
**JSON-LD + Hydra REST API generator for Symfony**

This a work in progress under active development.
This bundle *is not usable in production yet*.

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
* [FOSUserBundle](https://github.com/FriendsOfSymfony/FOSUserBundle) integration

Everything is fully customizable trough a powerful event system and strong OOP.
This bundle is documented and tested with Behat (take a look at [the `features/` directory](features/)).
 
## Installation

*This bundle rely heavily on features that will be introduced in Symfony 2.7.*
To test it now, you must use an experimental Symfony branch, see https://github.com/symfony/symfony/pull/13257#issuecomment-68943401

Use [Composer](http://getcomposer.org) to install the bundle:

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
    prefix: "/api" # Optional

api:
    resource: "."
    type:     "json-ld"
    prefix: "/api" # Optional
```

## Usage

### Configure

The first step if to name your API. Add the following lines in `app/config/config.yml`:

```yaml
dunglas_json_ld_api:
    title: Your API name
    description: The full description of your API
```

The name and the description you give will be accessible trough the auto-generated Hydra documentation.

### Map your entities

Imagine you have the following Doctrine entity classes:

```php
<?php

# src/AppBundle/Entity/Product.php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
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

use Doctrine\Common\Collections\ArrayCollection;
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
    "resource.product":
        class:     "Dunglas\JsonLdApiBundle\Resource"
        arguments: [ "AppBundle\Entity\Product" ]
        tags:      [ { name: "json-ld.resource" } ]

    "resource.offer":
        class:     "Dunglas\JsonLdApiBundle\Resource"
        arguments: [ "AppBundle\Entity\Offer" ]
        tags:      [ { name: "json-ld.resource" } ]
```

**You're done!**

You now have a fully featured API exposing your Doctrine entities 
Run the Symfony app (`app/console server:run`) and browse the API entrypoint at `http://localhos:8000/api`.

Interact with it using a REST client such as [Postman](https://chrome.google.com/webstore/detail/postman-rest-client/fdmmgilgnpjigdojojpjoooidkmcomcm)
and take a look at the usage examples in the [the `features` directory](features/).

## Advanced usage

### Filters

#### Fields

How to expose filters on collections? Just register them in your services definition.

To allow filtering the list of offers:

```yaml
services:
    "resource.offer":
        class:     "Dunglas\JsonLdApiBundle\Resource"
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
    "resource.offer":
        class:     "Dunglas\JsonLdApiBundle\Resource"
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
    "resource.product":
        class:     "Dunglas\JsonLdApiBundle\Resource"
        arguments: [ "AppBundle\Entity\Product", ~, [ "serialization_group1", "serialization_group2" ], [ "deserialization_group1", "deserialization_group2" ] ]
        tags:      [ { name: "json-ld.resource" } ]
```

The Hydra documentation will leverage specified serialization and deserialization to list exposed properties, if they are
readable or/and writable.

### Validation groups

You want to leverage Symfony's [validation groups](http://symfony.com/doc/current/book/validation.html#validation-groups)?

No problem. Edit your service declaration and add groups you want to use when the validation occurs:

```yaml
services:
    "resource.product":
        class:     "Dunglas\JsonLdApiBundle\Resource"
        arguments: [ "AppBundle\Entity\Product", ~, ~, ~, [ "group1", "group2" ] ]
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

### Using a custom `Resource` class

TODO

### Using a custom controller

TODO

## Resources

* [API-first et Linked Data avec Symfony](http://les-tilleuls.coop/slides/dunglas/slides-sfPot-2015-01-15/#/) (in french)

## TODO

* [x] Filters (hydra:search)
* [ ] Externals IRIs support
* [ ] Spec classes with PHPSpec
* [ ] Extended documentation
