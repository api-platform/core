# URI Variables

* Status: accepted
* Deciders: @dunglas @soyuka

Implementation: [#4408][pull/4408]

## Context and Problem Statement

For reference see the previously implemented [Resource identifier](0001-resource-identifiers.md) ADR. 
URI variables are the URI template (e.g. `/books/{id}`) variables (e.g. `id`). When defining alternate resources (see [Resource definition](0002-resource-definition.md)), we need more informations than only a property and a class. The tuple accepted in [Resource identifier](0001-resource-identifiers.md) is not enough. For example:

```php
<?php
use Company;

#[Get("/companies/{companyId}/users/{id}", [identifiers=["companyId" => [Company::class, "id"], "id" => [User::class, "id"]]])]
class User {
  #[ApiProperty(identifier=true)]
  public $id;
  public Company $company;
}
```

To make this work, API Platform needs to know what property of the class User has the link to Company. 

## Considered Options

* Keeping the tuple but adding a third value which is the property referenced by the parent class.
* Using a map to allow granularity configuration of the URI variables.

## Decision Outcome

We will use a map to define URI variables, for now these options are available:

```
uriTemplate: [
    'companyId' => [
        'class' => Company::class,
        'identifiers' => ['id'],
        'composite_identifier' => true,
        'property' => 'user'
    ],
    'id' => [
        'class' => User::class,
        'identifiers' => ['id']
    ]
]
```

Where `uriTemplate` keys are the URI template's variable names. Its value is a map where:

- `class` is the PHP FQDN to the class this value belongs to
- `identifiers` are the properties of the class to which we map the URI variable
- `composite_identifier` is used to match a single variable to multiple identifiers (`ida=1;idb=2` to `class::ida` and `class::idb`)
- `property` represents the property that has a link to the next identifier

As of PHP 8.1, PHP will support [nested attributes](https://wiki.php.net/rfc/new_in_initializers). We'll introduce a proper class as an alternative to the associative array when PHP 8.1 will be released.

Thanks to these we can build our query which in this case is (pseudo-SQL):

```
SELECT * FROM User::class u
JOIN Company::class c ON c.user = u.id AND c.id = :companyId
WHERE u.id = :id
```

## Links 

* Superseeds the [0001-resource-identifiers](0001-resource-identifiers.md) ADR.

[pull/4408]: https://github.com/api-platform/core/pull/4408 "URI variables implementation"
