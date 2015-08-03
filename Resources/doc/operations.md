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

If you want to disable some operations (e.g. the `DELETE` operation), can update it in your configuration.

```yaml
dunglas_api:
    resources:
        product:
            entry_class: "AppBundle\Entity\Product"
            item_operations: ["GET", "PUT" ]
            collection_operations: ["GET" ]
```

## Creating custom operations

DunglasApiBundle allows to register custom operations for collections and items.
Custom operations allow to customize routing information (like the URL and the HTTP method),
the controller to use (default to the built-in action of the `ResourceController` applicable
for the given HTTP method) and a context that will be passed to documentation generators.

You just need to add some configuration options:
```yaml
dunglas_api:
    resources:
        product:
            entry_class: "AppBundle\Entity\Product"
            item_operations: ["GET", "PUT" ]
            collection_operations: ["GET" ]
            item_custom_operations: 
                some_action:
                    methods: ["POST"]
                    path: "/product/{id}/_some_action" # optional
                    route: "some_action_route" # optional
                    controller: "AppBundle:Foo:bar"
                    context:
                         "@type": "hydra:Operation"
                         "hydra:title": "Do something"
                         "returns": "xmls:string"
            collection_custom_operations: 
                another_custom_action: 
                    methods: ["HEAD"]
                    controller: "AppBundle:Foo:baz"

```

Additionally to the default generated operations, this definition will expose a new `POST` operation for the `/products/{id}/_some_action` URL. When this URL is opened, the `AppBundle:Foo:bar` controller is called.
It will also add a new `HEAD` operation for `/products/`.

Previous chapter: [NelmioApiDocBundle integration](nelmio-api-doc.md)<br>
Next chapter: [Data providers](data-providers.md)
