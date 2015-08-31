# Validation

DunglasApiBundle use the Symfony validator to validate entities.
By default, it uses the default validation group, but this behavior is customizable.

## Using validation groups
The built-in controller is able to leverage Symfony's [validation groups](http://symfony.com/doc/current/book/validation.html#validation-groups).

To take care of them, edit your service declaration and add groups you want to use when the validation occurs:

```yaml
services:
    resource.product:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\Product" ]
        calls:
            -      method:    "initValidationGroups"
                   arguments: [ [ "group1", "group2" ] ]
        tags:      [ { name: "api.resource" } ]
```

With the previous definition, the validations groups `group1` and `group2` will be used when the validation occurs.

## Dynamic validation groups

If you need to dynamically determine which validation groups to use for an entity in different scenarios, use Symfony's [group sequence provider](http://symfony.com/doc/current/book/validation.html#group-sequence-providers).

Previous chapter: [Serialization groups and relations](serialization-groups-and-relations.md)<br>
Next chapter: [The event system](the-event-system.md)
