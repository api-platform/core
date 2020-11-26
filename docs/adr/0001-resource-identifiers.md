# Resource Identifiers

* Status: proposed
* Deciders: @dunglas @alanpoulain @soyuka

Technical Story: [#2126][pull/2126]
Implementation: [#3825][pull/3825]

## Context and Problem Statement

In API Platform, a resource is identified by [IRIs][rfc/IRI], for example `/books/1`. Internally, this is also known as a route with an identifier parameter named `id`: `/books/{id}`. This `id` parameter is then matched to the resource identifiers, known by the `ApiProperty` metadata when `identifier` is true. When multiple identifiers are found, composite identifiers map the value of `id` to the resource identifiers (eg: `keya=value1;keyb=value2`, where `keya` and `keyb` are identifiers of the resource). This behavior is suggested by the [URI RFC][rfc/URI].
Subresources IRIs have multiple parts, for example: `/books/{id}/author/{authorId}`. The router needs to know that `id` matches the `Book` resource, and `authorId` the `Author` resource. To do so, a Tuple representing the class and the property matching each parameter is linked to the route, for example: `id: [Book, id], authorId: [User, id]`.
By normalizing the shape of (sub-)resources (see [0000-subresources-definition][0000-subresources-definition]), we need to normalize the resource identifiers.

## Decision Outcome

Declare explicit resource `identifiers` that will default to `id: [id, Resource]` with composite identifiers. Allow composite identifiers to be disabled if needed.

### Examples

Define a route `/users/{id}`:

```php
/**
 * @ApiResource
 */
 class User {
  /** @ApiProperty(identifier=true) */
  public int $id;
 }
```

Or 

```php
/**
 * @ApiResource(identifiers={"id": {User::class, "id"}})
 */
 class User {
  /** @ApiProperty(identifier=true) */
  public int $id;
 }
```

Define a route `/users/{username}` that uses the username identifier:

```php
/**
 * @ApiResource(identifiers={"username"})
 */
 class User {
  /** @ApiProperty(identifier=true) */
  public string $username;
 }
```

Or

```php
/**
 * @ApiResource(identifiers={"username": {User::class, "username"}})
 */
 class User {
  /** @ApiProperty(identifier=true) */
  public string $username;
 }
```

Define a route `/users/{username}` that uses the property shortName:

```php
/**
 * @ApiResource(identifiers={"username"={User::class, "shortName"}})
 */
 class User {
  /** @ApiProperty(identifier=true) */
  public string $shortName;
 }
```

Define a route `/users/{composite}` that uses composite identifiers `/users/keya=value1;keyb=value2`:

```php
/**
 * @ApiResource(identifiers={"composite"})
 */
 class User {
  /** @ApiProperty(identifier=true) */
  public string $keya;
  /** @ApiProperty(identifier=true) */
  public string $keyb;
 }
```

Define a route `/users/{keya}/{keyb}`:

```php
/**
 * @ApiResource(identifiers={"keya", "keyb"}, compositeIdentifier=false)
 */
 class User {
  /** @ApiProperty(identifier=true) */
  public string $keya;
  /** @ApiProperty(identifier=true) */
  public string $keyb;
 }
```

Complex version: 

```php
/**
 * @ApiResource(identifiers={"keya"={User::class, "keya"}, "keyb"={User::class, "keyb"}}, compositeIdentifier=false)
 */
 class User {
  /** @ApiProperty(identifier=true) */
  public string $keya;
  /** @ApiProperty(identifier=true) */
  public string $keyb;
 }
```

Define a subresource `/companies/{companyId}/users/{id}`: 

```php
/**
 * @ApiResource(path="/users", identifiers={"id": {User::class, "id"}})
 * @ApiResource(path="/companies/{companyId}/users", identifiers={"companyId": {Company::class, "id"}, "id": {User::class, "id"}})
 */
class User {
  /** @ApiProperty(identifier=true) */
  public int $id;

  /** @var Company[] */
  public array $companies = [];
}

class Company {
  /** @ApiProperty(identifier=true) */
  public int $id;

  /** @var User[] */
  public $users;
}
```

## Links 

* Adds up to the [0000-subresources-definition][0000-subresources-definition] rework.

[0000-subresources-definition]: ./0000-subresources-definition "Subresources definition"
[pull/2126]: https://github.com/api-platform/core/pull/2126 "Ability to specify identifier property of custom item operations"
[pull/3825]: https://github.com/api-platform/core/pull/3825 "Rework to improve and simplify identifiers management"
[rfc/IRI]: https://tools.ietf.org/html/rfc3987 "RFC3987"
[rfc/URI]: https://tools.ietf.org/html/rfc3986#section-3.3 "RFC 3986"
