# Serialization groups and relations

## Using serialization groups

Symfony 2.7 introduced [serialization (and deserialization) groups support](http://symfony.com/blog/new-in-symfony-2-7-serialization-groups)
in the Serializer component. Specifying to the API system the groups to use is damn easy:

```yaml
services:
    resource.product:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Product" ]
        calls:
            -      method:    "initNormalizationContext"
                   arguments: [ { groups: [ "serialization_group1", "serialization_group2" ] } ]
            -      method:    "initDenormalizationContext"
                   arguments: [ { groups: [ "deserialization_group1", "deserialization_group2" ] } ]
        tags:      [ { name: "api.resource" } ]
```

The built-in controller and the Hydra documentation generator will leverage specified serialization and deserialization
to give access only to exposed properties and to guess if they are readable or/and writable.

## Embedding relations

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

### Normalization

From a performance point of view, it's sometimes necessary to avoid extra HTTP requests. It is possible to embed related
objects (or only some of their properties) directly in the parent response trough serialization groups.
By using the following serizalization groups annotations (`@Groups`) and this updated service definition, a JSON representation
of the product is embedded in the offer response:

```php
<?php

// src/AppBundle/Entity/Offer.php

namespace AppBundle\Entity;

use Symfony\Component\Serializer\Annotation\Groups;

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

// src/AppBundle/Entity/Product.php

namespace AppBundle\Entity;

use Symfony\Component\Serializer\Annotation\Groups;

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
                   arguments: [ { groups: [ "offer" ] } ]
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

### Denormalization

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

Previous chapter: [Filters](filters.md)<br>
Next chapter: [Validation](validation.md)
