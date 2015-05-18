# Using external (JSON-LD) vocabularies

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

Next chapter: [Using external (JSON-LD) vocabularies](external-vocabularies.md)
Previous chapter: [Resources](resources.md)
