# Link

* Status: accepted
* Deciders: @dunglas, @soyuka, @alanpoulain

Implementation: [#4536][pull/4536]

## Context and Problem Statement

The [URI Variables](0003-uri-variables.md) ADR introduces a new `UriVariable` POPO.
In GraphQL, having URI variables make no sense: this object needs either an alias or needs to be named differently.

## Considered Options

* Create a `Traverser` alias for GraphQL.
* Rename `UriVariable` to `Link`.

## Decision Outcome

We chose to rename `UriVariable` to `Link` in order to simplify the codebase.
However the `uriVariables` parameter in the REST operations will not be renamed since it makes sense to have this name.
GraphQL operations don't need to have links at the operation level, a `Link` attribute on the property will be used instead if necessary (the main use case is when a `toProperty` is necessary).

To follow this renaming, the properties in `Link` are also renamed:
- `targetClass` becomes `fromClass`
- `inverseProperty` becomes `fromProperty`
- `property` becomes `toProperty`

New properties are also necessary:
- `toClass` for GraphQL because GraphQL needs to find the right `Link`
- `expandedValue` for REST in order to convert an URI variable to the corresponding route part (for instance in the case of the URI template `/questions/{questionId}/{questionAnswer}/related_questions`, the expanded value for `questionAnswer` could be `answer`)

### Classical Example

```php
<?php

#[Query]
#[Get]
class Company
{
  public $id;

  #[ORM\OneToMany(targetEntity: Employee::class, mappedBy: 'company')]
  // will automatically create:
  #[Link(fromClass: Company::class, fromProperty: 'employees')]
  /** @var Employee[] */
  public iterable $employees;
}
```

```php
<?php

#[Query]
#[GetCollection('/companies/{companyId}/employees', uriVariables: [
    'companyId' => new Link(
        fromClass: Company::class,
        fromProperty: 'employees'
    )
])]
class Employee
{
  public $id;

  #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'employees')]
  public Company $company;
}
```

The GraphQL query equivalent to a `GET` to `/companies/2/employees` can now be done:

```graphql
{
  companies(id: "/companies/2") {
    employees {
      edges {
        node {
          id
        }
      }
    }
  }
}
```

### Inverted Example

In this example, the relation between the employee and the company is only hold by the employee.

```php
<?php

#[Query]
#[GetCollection('/companies/{companyId}/employees', uriVariables: [
    'companyId' => new Link(
        fromClass: Company::class,
        toProperty: 'company'
    )
])]
class Employee
{
  public $id;

  #[ORM\ManyToMany(targetEntity: Company::class)]
  #[ORM\JoinTable(name: 'employees_companies')]
  #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'id')]
  #[ORM\InverseJoinColumn(name: 'company_id', referencedColumnName: 'id', unique: true)]
  public Company $company;
}
```

```php
<?php

#[Query]
#[Get]
class Company
{
  public $id;

  #[Link('company')]
  // equivalent to:
  #[Link(fromClass: Company::class, toClass: Employee::class, toProperty: 'company')]
  /** @var Employee[] */
  public iterable $employees;
}
```

The GraphQL query equivalent to a `GET` to `/companies/2/employees` can now be done:

```graphql
{
  companies(id: "/companies/2") {
    employees {
      edges {
        node {
          id
        }
      }
    }
  }
}
```

[pull/4536]: https://github.com/api-platform/core/pull/4536 "Link implementation"
