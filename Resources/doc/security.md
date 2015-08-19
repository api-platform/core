# Security

To completely disable some operations from your application, refer to [Disabling operations](operations.md#disabling-operations).

This bundle relies on security features provided by the [Symfony Security Component](http://symfony.com/doc/current/book/security.html). To restrict access to some operations, use [Access control rules](security.md#access-control-rules).

It is also possible to use the [Event system](the-event-system.md) for more advanced logic or even [Custom controllers](controllers.md#using-a-custom-controller) if you really need to.

## Access control rules

You can use [Security expressions](http://symfony.com/doc/current/cookbook/expression/expressions.html#book-security-expressions) to control access to each operation.

```yaml
# app/config/services.yml
services:
    resource.product:
        parent: api.resource
        arguments:
            - AppBundle\Entity\Product
        calls:
            - method: initAccessControlRules
              arguments:
                  - collection:
                        POST: "has_role('ROLE_ADMIN')"
                    item:
                        PUT: "has_role('ROLE_ADMIN')"
                        DELETE: "has_role('ROLE_ADMIN')"
        tags:
            - name: api.resource
```

Previous chapter: [Content negotiation](content-negotiation.md)<br>
Next chapter: [Performance](performance.md)
