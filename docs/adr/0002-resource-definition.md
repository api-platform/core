# Resource definition

* Status: accepted
* Deciders: @dunglas, @soyuka, @vincentchalamon, @GregoireHebert

## Context and Problem Statement

The API Platform `@ApiResource` annotation was initially created to represent a Resource as defined in [Roy Fielding's dissertation about REST](https://www.ics.uci.edu/~fielding/pubs/dissertation/rest_arch_style.htm#sec_5_2_1_1) in correlation with [RFC 7231 about HTTP Semantics](https://httpwg.org/specs/rfc7231.html#resources). This annotation brings some confusion as it mixes concepts of resources and operations. Here we discussed how we could revamp API Platform's resource definition using PHP8 attributes, being as close as we can to Roy Fielding's thesis vocabulary.

## Considered Options

* Declare multiple ApiResource on a PHP Class [see Subresources definition](./0000-subresources-definition.md)
* Declare operations in conjunction with resources using two attributes: `Resource` and `Operation`
* Use HTTP Verb to represent operations with a syntax sugar for collections (`CGET`?)

## Decision Outcome

As Roy Fielding's thesis states:

> REST uses a resource identifier to identify the particular resource involved in an interaction between components. REST connectors provide a generic interface for accessing and manipulating the value set of a resource, regardless of how the membership function is defined or the type of software that is handling the request. 

In API Platform, this resource identifier is also named [IRI (Internationalized Resource Identifiers)](https://tools.ietf.org/html/rfc3987). Following these recommendations, applied to PHP, we came up with the following [PHP 8 attributes](https://www.php.net/manual/en/language.attributes.php):

```php
<?php

#[ApiResource]
class Users
{
    #[ApiProperty(types="hydra:member")]
    public iterable $member = [];

    public float $averageRate;
}

#[ApiResource("/companies/{companyId}/users/{id}", normalizationContext=["groups"= [....]]), operations={}]
#[ApiResource(normalizationContext=["groups"= [....]], operations=[
 new Get(),
 new Post(),
])]
class User
{
    #[ApiProperty(identifier=true)]
    public $id;
}
```

Under the hood, API Platform would declare two routes Representing the `/users` resource:

- GET /users
- POST /users

and three routes representing the `/users/{id}` resource:

- GET /users/{id}
- PUT /users/{id}
- DELETE /users/{id}

For convenience and to ease the upgrade path, these would still be available on a single class:

```php
<?php

#[ApiResource]
class User {}
```

corresponding to 

```php
<?php

#[Get]
#[GetCollection]
#[PostCollection]
#[Put]
#[Delete]
class User {}
```

Verbs declared on a PHP class define API Platform operations. The `ApiResource` attributes would become optional and the only thing needed is to specify at least a verb and an IRI representing the Resource. Some examples:

<table>
    <tr>
        <th>
            Code
        </th>
        <th>
            Operations
        </th>
    </tr>
    <tr>
        <td>
            <pre lang="php">
#[Get]
class User {}
            </pre>
        </td>
        <td>
            <ul>
                <li>GET /users/{id}</li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>
            <pre lang="php">
#[GetCollection]
class User {}
            </pre>
        </td>
        <td>
            <ul>
                <li>GET /users</li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>
            <pre lang="php">
#[Get("/users")]
class User {}
            </pre>
        </td>
        <td>
            <ul>
                <li>GET /users</li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>
            <pre lang="php">
#[Post("/users")]
#[Patch("/users/{id}")]
class User {}
            </pre>
        </td>
        <td>
            <ul>
                <li>POST /users</li>
                <li>PATCH /users/{id}</li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>
            <pre lang="php">
// See these as aliases to the `/users/{id}` Resource:
#[Get("/companies/{companyId}/users/{id}")]
#[Delete("/companies/{companyId}/users/{id}")]
class User {}
            </pre>
        </td>
        <td>
            <ul>
                <li>GET /companies/{companyId}/users/{id}</li>
                <li>DELETE /companies/{companyId}/users/{id}</li>
            </ul>
        </td>
    </tr>
</table>

To link these operations with identifiers, refer to [URI Variables decision record](0003-uri-variables.md), for example:

```php
<?php
use Company;

#[Get(
    uriTemplate: "/companies/{companyId}/users/{id}", 
    uriVariables: [
        "companyId" => ["class" => Company::class, "identifiers" => ["id"], "property" => "user"], 
        "id" => ["class" => User::class, "identifiers" => ["id"]]
    ]
)]
class User {
  #[ApiProperty(identifier=true)]
  public $id;
  public Company $company;
}
```

The `ApiResource` attribute could be used to set defaults properties on operations:

```php
<?php

#[ApiResource(normalizationContext=["groups"= [....]])]
#[Get("/users/{id}")]
class User {}
```

These properties can also be specified directly on the verb attribute:

```php
<?php

#[Get("/users/{id}", normalizationContext=["group"])]
class User {}
```

Internally, HTTP verbs are aliases to the Resource Attribute holding a method and a default path. The `ApiResource` attribute is a reflection of the underlying metadata:

```php
<?php

namespace ApiPlatform\Metadata;

class ApiResource {
    public string $iri;
    public bool $mercure;
    public string $security;
    public bool $paginationEnabled;
    // etc.

    /** this was named $attributes before **/
    public array $extraProperties;
}
```

For GraphQL, `Query`, `Mutation` and `Subscription` will be added. 

## Options declined

An `Operation` attribute was proposed, but we want to keep the code base small and decided verb attributes where sufficient.

The initially proposed `CGet` will be named `GetCollection`. It is only a shortcut to what **used to be called** `collectionOperation` on the `GET` verb. To remove confusion around `collectionOperations` and `itemOperations`, these terms will be deprecated in the code-base. To distant ourselves from the `CRUD` pattern, we also declined `List` and `Create` as we want to focus on Resource based architectures. `RPC` routes will be easy to add using the `Post` verb if required.
