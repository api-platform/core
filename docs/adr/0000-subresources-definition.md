# Subresource Definition

* Status: proposed
* Deciders: @dunglas, @vincentchalamon, @soyuka, @GregoireHebert, @Deuchnord

## Context and Problem Statement

Subresources introduced in 2017 ([#904][pull/904]) the `ApiSubresource` annotation. This definition came along with its own set of issues ([#2706][issue/2706]) and needs a refreshment. On top of that, write support on subresources is a wanted feature and it is hard to implement currently ([#2598][pull/2598]) (See [ADR-0001-subresource-write-support](./0001-subresource-write-support.md)). How can we revamp the Subresource definition to improve the developer experience and reduce the complexity?

## Considered Options

* Fix the current `ApiSubresource` annotation
* Use multiple `ApiResource` to declare subresources and deprecate `ApiSubresource`
* Deprecate subresources

## Decision Outcome

We choose to use multiple `ApiResource` annotations to declare subresources on a given Model class: 

* Subresource declaration is an important feature and removing it would harm the software. 
* The `ApiSubresource` annotation is declared on a Model's properties, which was identified as the root of several issues. For example, finding what class it is defined on ([#3458][issue/3458]). Having multiple `ApiResource` would improve a lot the declaration of our internal metadata and would cause less confusion for developers. 
* The `path` of these multiple `ApiResource` needs to be explicitly described. 
* An `ApiResource` is always defined on the Resource it represents: `/companies/1/users` outputs Users and should be defined on the `User` model.
* PropertyInfo and Doctrine metadata can be used to define how is the Resource identified according to the given path.

### Examples

Get Users belonging to the company on (`/companies/1/users`);

```php
/**
 * @ApiResource(path="/users")
 * @ApiResource(path="/companies/{companyId}/users")
 */
class User {
  /** @ApiProperty(identifier=true) */
  public int $id;

  /** @var Company[] */
  public array $companies = [];
}
```

With explicit identifiers, the tuple is explained in [ADR-0002-identifiers](./0002-identifiers) `{parameterName: {Class, property}}`:

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
```

Two-level subresource to get the Users belonging to the Company #1 located in France `/countries/fr/companies/1/users`: 

```php
/**
 * @ApiResource(path="/users")
 * @ApiResource(path="/countries/{countryId}/companies/{companyId}/users")
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

  /** @var Country[] **/
  public array $countries = [];
}

class Country {
  /** @ApiProperty(identifier=true) */
  public string $shortName;
}
```

With explicit identifiers:

```php
/**
 * @ApiResource(path="/users", identifiers={"id": {User::class, "id"}})
 * @ApiResource(path="/countries/{countryId}/companies/{companyId}/users", identifiers={"companyId": {Company::class, "id"}, "countryId": {Country::class, "shortName"}, "id": {User::class, "id"}})
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

  /** @var Country[] **/
  public array $countries = [];
}

class Country {
  /** @ApiProperty(identifier=true) */
  public string $shortName;
}
```

Get the company employees or administrators `/companies/1/administrators`:

```php
/**
 * @ApiResource(path="/users")
 * @ApiResource(path="/companies/{companyId}/administrators")
 * @ApiResource(path="/companies/{companyId}/employees")
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

  /** @var User[] **/
  public array $employees;

  /** @var User[] **/
  public array $administrators;
}
```

This example will require a custom DataProvider as the discriminator needs to be explicit.

## Links

* [Subresource refactor][pull/3689]


[pull/904]: https://github.com/api-platform/core/pull/904  "Subresource feature"
[issue/2706]: https://github.com/api-platform/core/issues/2706 "Subresource RFC"
[pull/2598]: https://github.com/api-platform/core/pull/2598 "Subresource write support"
[issue/3458]: https://github.com/api-platform/core/pull/3458 "Subresource poor DX"
[pull/3689]: https://github.com/api-platform/core/pull/3689 "Revamp subresource"
