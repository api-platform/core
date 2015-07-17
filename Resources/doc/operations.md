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

You can specify the list of operation to enable by setting two properties `collectionOperations` and `itemOperations`.

The following example allow only GET on collections and GET / PUT on item calls.

```yaml
# ...

    resource.product:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Product" ]
        properties: properties: { collectionOperations: ["GET"], itemOperations: ["GET", "PUT"] }
        tags:      [ { name: "api.resource" } ]
```

## Creating custom operations

DunglasApiBundle allows to register custom operations for collections and items.
Custom operations allow to customize routing information (like the URL and the HTTP method),
the controller to use (default to the built-in action of the `ResourceController` applicable
for the given HTTP method) and a context that will be passed to documentation generators.

A convenient factory is provided to build `Dunglas\ApiBundle\Api\Operation\Operation` instances.
This factory guesses good default values for options such as the route name and its associated URL
by inspecting the given `Resource` instance. All guessed values can be override.

If you want to use custom controller action, [refer to the dedicated documentation](controllers.md).

DunglasApiBundle is smart enough to automatically register routes in the Symfony routing system and to document
operations in the Hydra vocab.

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
            -    "/products/{id}/custom"           # Path
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
            -      method:    "initItemOperations"
                   arguments: [ [ "@resource.product.item_operation.get", "@resource.product.item_operation.put", "@resource.product.item_operation.custom_get" ] ]
        tags:      [ { name: "api.resource" } ]
```

Additionally to the default generated `GET` and `PUT` operations, this definition will expose a new `GET` operation for
the `/products/{id}/custom` URL. When this URL is opened, the `AppBundle:Custom:custom` controller is called.

Previous chapter: [NelmioApiDocBundle integration](nelmio-api-doc.md)<br>
Next chapter: [Data providers](data-providers.md)
