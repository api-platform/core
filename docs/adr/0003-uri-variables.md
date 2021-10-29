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

#[ApiResource("/companies/{companyId}/employees/{id}", [identifiers=["companyId" => [Company::class, "id"], "id" => [Employee::class, "id"]]])]
class Employee {
  #[ApiProperty(identifier=true)]
  public $id;
  public Company $company;
}
```

To make this work, API Platform needs to know what property of the class Employee has the link to Company. 

## Considered Options

* Keeping the tuple but adding a third value which is the property referenced by the parent class.
* Using a map to allow granularity configuration of the URI variables.

## Decision Outcome

We will use a POPO to define URI variables, for now these options are available:

```
uriVariables: [
    'companyId' => new UriVariable(
        targetClass: Company::class,
        targetProperty: null,
        property: 'company'
        identifiers: ['id'],
        compositeIdentifier: true,
    ],
    'id' => new UriVariable( 
        targetClas: Employee::class,
        identifiers: ['id']
    )
]
```

Where `uriVariables` keys are the URI template's variable names. Its value is a map where:

- `targetClass` is the PHP FQDN of the class this value belongs to
- `property` represents the property, the URI Variable is mapped to in the current class
- `targetProperty` represents the property, the URI Variable is mapped to in the related class and is not available in the current class
- `identifiers` are the properties of the targetClass to which we map the URI variable
- `compositeIdentifier` is used to match a single variable to multiple identifiers (`ida=1;idb=2` to `class::ida` and `class::idb`)

As of PHP 8.1, PHP will support [nested attributes](https://wiki.php.net/rfc/new_in_initializers), it'll be required to configure the `uriVariables`.

Thanks to these we can build our query which in this case is (pseudo-SQL) to fetch the employee belonging to a company (`/companies/{companyId}/employees/{id}`):

```sql
SELECT * FROM Employee::class e
JOIN e.company c 
WHERE e.id = :id AND c.id = :companyId
```

### Example for a Employee resource that belongs to a Company:

```php
<?php

#[ApiResource("/companies/{companyId}/employees/{id}")]
class Employee {
  public $id;

  // Note that this is the same as defining UriVariable(property: company), see below on the complex example
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
SELECT * FROM Employee::class e
JOIN e.company c 
WHERE e.id = :id AND c.id = :companyId
```

### Example the Company resource that belongs to an employee (using the inverse relation):

```php
<?php

#[ApiResource]
class Employee {
  public $id;
  public Company $company;
}
```

```php
<?php

#[ApiResource("/employee/{employeeId}/company", uriVariables: [
    'employeeId' => new UriVariable(Employee::class, 'company')
])]
class Company {
  public $id;
}
```

Note that the above is a shortcut for: `new UriVariable(targetClass: Employee::class, targetProperty: 'company')`

Corresponding DQL: 

```sql
SELECT * FROM Company::class c
JOIN Employee::class e WITH e.companyId = c.id
WHERE e.id = :id
```

Or if you have the inverse relation mapped to a property:


```php
<?php

#[ApiResource]
class Employee {
  public $id;
  public Company $company;
}
```

```php
<?php

#[ApiResource("/employees/{employeeId}/company")]
class Company {
  #[ApiProperty(identifier=true)]
  public $id;

  #[UriVariable('employeeId')]
  /** @var Employee[] */
  public $employees;
}
```

Corresponding DQL: 

```sql
SELECT * FROM Company::class c
JOIN c.employees u
WHERE u.id = :id
```

### Example to get the employees behind a company: 

```php
<?php

#[ApiResource("/companies/{companyId}/employees")]
#[GetCollection]
class Employee {
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
SELECT * FROM Employee::class e
JOIN e.company c 
WHERE c.id = :companyId
```

### Example for a Employee resource that belongs to a Company (complex definition):

```php
<?php

#[ApiResource("/companies/{companyId}/employees/{id}", uriVariables: [
    'companyId' => new UriVariable(
        targetClass: Company::class,
        property: 'company',
        identifiers: ['id'],
        compositeIdentifier: true
    ],
    'id' => new UriVariable(
        targetClass: Employee::class,
        identifiers: ['id']
    )
])]
class Employee {
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
SELECT * FROM Employee::class e
JOIN e.company c 
WHERE e.id = :id AND c.id = :companyId
```

### Example the Company resource that belongs to a Employee (using the inverse relation, complex definition):

```php
<?php

#[ApiResource]
class Employee {
  public $id;
  public Company $company;
}
```

```php
<?php

#[ApiResource("/employees/{employeeId}/company", uriVariables: [
    'employeeId' => new UriVariable(
        targetClass: Employee::class, 
        targetProperty: 'company'
        property: null,
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
JOIN Employee::class e WITH e.companyId = c.id
WHERE e.id = :id
```

Or if you have the inverse relation mapped to a property:


```php
<?php

#[ApiResource]
class Employee {
  public $id;
  public Company $company;
}
```

```php
<?php

#[ApiResource("/employees/{employeeId}/company", uriVariables: [
    'employeeId' => new UriVariable(
        targetClass: Employee::class,
        property: 'employees',
        identifiers: ['id'],
    )
])]
class Company {
  #[ApiProperty(identifier=true)]
  public $id;

  /** @var Employee[] */
  public $employees;
}
```

Corresponding DQL: 

```sql
SELECT * FROM Company::class c
JOIN c.employees e
WHERE e.id = :id
```

### Example to get the employees behind a company (complex version): 

```php
<?php

#[ApiResource("/companies/{companyId}/employees", uriVariables: [
    'companyId' => new UriVariable(
        targetClass: Company::class,
        identifiers: ['id'],
        compositeIdentifier: true,
        property: 'company'
    )
])]
#[GetCollection]
class Employee {
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
SELECT * FROM Employee::class e
JOIN e.company c 
WHERE c.id = :companyId
```

## Links 

* Supersedes the [0001-resource-identifiers](0001-resource-identifiers.md) ADR.

[pull/4408]: https://github.com/api-platform/core/pull/4408 "URI variables implementation"
