# Getting started

## Installing DunglasApiBundle

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

## Configuring the API

The first step is to name your API. Add the following lines in `app/config/config.yml`:

```yaml
# Default configuration for extension with alias: "dunglas_api"
dunglas_api:

    # The title of the API.
    title:                "Your API name" # Required

    # The description of the API.
    description:          "The full description of your API" # Required

    # The caching service to use. Set to "dunglas_api.mapping.cache.apc" to enable APC metadata caching.
    cache:                false

    # Enable the FOSUserBundle integration.
    enable_fos_user:      false
    collection:

        # The default order of results. (supported by Doctrine: ASC and DESC)
        order:                null
        pagination:

            # The name of the parameter handling the page number.
            page_parameter_name:  page
            items_per_page:

                # The default number of items perm page in collections.
                number:               30

                # Allow the client to change the number of elements by page.
                enable_client_request:  false

                # The name of the parameter to change the number of elements by page client side.
                parameter_name:       itemsPerPage
```

The name and the description you give will be accessible through the auto-generated Hydra documentation.

## Mapping the entities

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

## Registering the services

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

Next chapter: [Operations](operations.md)
