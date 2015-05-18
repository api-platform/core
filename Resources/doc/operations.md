# Operations

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


## Disabling operations

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

## Creating custom operations

Sometimes, it can be useful to [create custom controller](8-custom-controllers.md) actions. DunglasApiBundle allows to register custom operations
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

Additionally to the default generated `GET` and `PUT` operations, this definition will expose a new `GET` operation for
the `/products/{id}/custom` URL. When this URL is opened, the `AppBundle:Custom:custom` controller is called.

Next chapter: [Data providers](data-providers.md)
Previous chapter: [Getting Started](getting-started.md)
