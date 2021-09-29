# URI Variables

* Status: accepted
* Deciders: @dunglas, @soyuka

Implementation: [#4408][pull/4408]

## Context and Problem Statement

For reference see the previously implemented [Resource identifier](0001-resource-identifiers.md) ADR. 
URI variables are the URI template (e.g. `/books/{id}`) variables (e.g. `id`). When defining alternate resources (see [Resource definition](0002-resource-definition.md)), we need more information than only a property and a class. The tuple accepted in [Resource identifier](0001-resource-identifiers.md) is not enough. For example:

```php
<?php
use Company;

#[ApiResource("/companies/{companyId}/users/{id}", [identifiers=["companyId" => [Company::class, "id"], "id" => [User::class, "id"]]])]
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

We will use a POPO to define URI variables, for now these options are available:

```
uriVariables: [
    'companyId' => new UriVariable(
        targetClass: Company::class,
        inversedBy: null,
        mappedBy: 'company'
        identifiers: ['id'],
        compositeIdentifier: true,
    ],
    'id' => new UriVariable( 
        targetClas: User::class,
        identifiers: ['id']
    )
]
```

Where `uriVariables` keys are the URI template's variable names. Its value is a map where:

- `targetClass` is the PHP FQDN to the class this value belongs to
- `mappedBy` is the property this uri variable is mapped to, usually a property of the next targetClass
- `inversedBy` is the inversed property of the next targetClass this variables maps to
- `identifiers` are the properties of the targetClass to which we map the URI variable
- `compositeIdentifier` is used to match a single variable to multiple identifiers (`ida=1;idb=2` to `class::ida` and `class::idb`)

As of PHP 8.1, PHP will support [nested attributes](https://wiki.php.net/rfc/new_in_initializers), it'll be required to configure the `uriVariables`.

Thanks to these we can build our query which in this case is (pseudo-SQL) to fetch the user belonging to a company (`/companies/{companyId}/users/{id}`):

```sql
SELECT * FROM User::class u
JOIN u.company c 
WHERE u.id = :id AND c.id = :companyId
```

### Example for a User resource that belongs to a Company:

```php
<?php

#[ApiResource("/companies/{companyId}/users/{id}")]
class User {
  public $id;
  #[UriVariable('companyId')]
  public Company $company;
}
```

```php
<?php

#[ApiResource]
class Company {
  public $id;
}
```

Generated DQL:

```sql
SELECT * FROM User::class u
JOIN u.company c 
WHERE u.id = :id AND c.id = :companyId
```

### Example the Company resource that belongs to a user (using the inversed relation):

```php
<?php

#[ApiResource]
class User {
  public $id;
  public Company $company;
}
```

```php
<?php

#[ApiResource("/users/{userId}/company", uriVariables: [
    'userId' => new UriVariable(User::class, 'company')
])]
class Company {
  public $id;
}
```

Corresponding DQL: 

```sql
SELECT * FROM Company::class c
JOIN User::class u WITH u.companyId = c.id
WHERE u.id = :id
```

Or if you have the inversed relation mapped to a property:


```php
<?php

#[ApiResource]
class User {
  public $id;
  public Company $company;
}
```

```php
<?php

#[ApiResource("/users/{userId}/company")]
class Company {
  #[ApiProperty(identifier=true)]
  public $id;

  #[UriVariable('userId')]
  /** @var User[] */
  public $users;
}
```

Corresponding DQL: 

```sql
SELECT * FROM Company::class c
JOIN c.users u
WHERE u.id = :id
```

### Example to get the users behind a company: 

```php
<?php

#[ApiResource("/companies/{companyId}/users", collection: true)]
class User {
  public $id;
  #[UriVariable('companyId')]
  public Company $company;
}
```

```php
<?php

#[ApiResource]
class Company {
  #[ApiProperty(identifier=true)]
  public $id;
}
```

Generated DQL: 

```sql
SELECT * FROM User::class u
JOIN u.company c 
WHERE c.id = :companyId
```

### Example for a User resource that belongs to a Company (complex definition):

```php
<?php

#[ApiResource("/companies/{companyId}/users/{id}", uriVariables: [
    'companyId' => new UriVariable(
        targetClass: Company::class,
        mappedBy: 'company',
        identifiers: ['id'],
        compositeIdentifier: true
        identifiers: ['id']
    ],
    'id' => new UriVariable(
        targetClass: User::class,
        identifiers: ['id']
    )
])]
class User {
  #[ApiProperty(identifier=true)]
  public $id;
  public Company $company;
}
```

```php
<?php

#[ApiResource]
class Company {
  #[ApiProperty(identifier=true)]
  public $id;
}
```

Generated DQL:

```sql
SELECT * FROM User::class u
JOIN u.company c 
WHERE u.id = :id AND c.id = :companyId
```

### Example the Company resource that belongs to a user (using the inversed relation, complex definition):

```php
<?php

#[ApiResource]
class User {
  public $id;
  public Company $company;
}
```

```php
<?php

#[ApiResource("/users/{userId}/company", uriVariables: [
    'userId' => new UriVariable(
        targetClass: User::class, 
        inversedBy: 'company'
        mappedBy: null,
        identifiers: ['id'],
        compositeIdentifier: true
    )
])]
class Company {
  #[ApiProperty(identifier=true)]
  public $id;
}
```

Corresponding DQL: 

```sql
SELECT * FROM Company::class c
JOIN User::class u WITH u.companyId = c.id
WHERE u.id = :id
```

Or if you have the inversed relation mapped to a property:


```php
<?php

#[ApiResource]
class User {
  public $id;
  public Company $company;
}
```

```php
<?php

#[ApiResource("/users/{userId}/company", uriVariables=[
    'userId' => new UriVariable(
        targetClass: User::class,
        mappedBy: 'users',
        identifiers: ['id'],
        
    )
])]
class Company {
  #[ApiProperty(identifier=true)]
  public $id;

  /** @var User[] */
  public $users;
}
```

Corresponding DQL: 

```sql
SELECT * FROM Company::class c
JOIN c.users u
WHERE u.id = :id
```

### Example to get the users behind a company (complex version): 

```php
<?php

#[ApiResource("/companies/{companyId}/users", collection: true, uriVariables: [
    'companyId' => new UriVariable(
        targetClass: Company::class,
        identifiers: ['id'],
        compositeIdentifier: true,
        mappedBy: 'company'
    )
])]
class User {
  #[ApiProperty(identifier=true)]
  public $id;
  public Company $company;
}
```

```php
<?php

#[ApiResource]
class Company {
  #[ApiProperty(identifier=true)]
  public $id;
}
```

Generated DQL: 

```sql
SELECT * FROM User::class u
JOIN u.company c 
WHERE c.id = :companyId
```

## Links 

* Supersedes the [0001-resource-identifiers](0001-resource-identifiers.md) ADR.

[pull/4408]: https://github.com/api-platform/core/pull/4408 "URI variables implementation"
