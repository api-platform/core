# Subresource definition

* Status: proposed
* Deciders: @dunglas, @vincentchalamon, @soyuka, @GregoireHebert, @Deuchnord

## Context and Problem Statement

Subresources introduced in 2017 (#904) introduced the `ApiSubresource` annotation. This definition came along with its own set of issues (#2706) and needs a refreshment. On top of that, write support on subresources is a wanted feature and it is hard to implement currently (#2598) (See [0001-subresource-write-support](./0001-subresource-write-support.md)). How can we revamp subresources to improve the developer experience and reduce the complexity?

## Considered Options

* Fix the actual `ApiSubresource` annotation
* Use multiple `ApiResource` to declare subresources and deprecate `ApiSubresource`
* Deprecate subresources

## Decision Outcome

We choose to use multiple `ApiResource` annotations to declare subresources on a given Model class: 

* Subresource declaration is an important feature and removing it would harm the software. 
* The `ApiSubresource` annotation is declared on a Model's properties, which was identified as the root of several issues. For example, finding what class it is defined on (#3458). Having multiple `ApiResource` would improve a lot the declaration of our internal metadata and would cause less confusion for developers. 
* The `path` of these multiple `ApiResource` needs to be implicitly described. 

### Examples

A Company resource with a Company Users subresource on (`/companies/1/users`);

```php
/**
 * @ApiResource()
 * @ApiResource(path="/companies/{companyId}/users")
 */
class Company {
  public int $id;
  public array $users = [];
}
```

With explicit identifiers:

```php
/**
 * @ApiResource()
 * @ApiResource(path="/companies/{companyId}/users", identifiers={"companyId": {Company::class, "id"}})
 */
class Company {
  public int $id;
  public array $users = [];
}
```

Two-level subresource to get the users belonging to the company 1 located in France `/countries/fr/companies/1/users`: 

```php
/**
 * @ApiResource()
 * @ApiResource(path="/countries/{countryId}/companies/{companyId}/users")
 */
class Country {
  public int $id;
  public array $companies = [];
}
```

With explicit identifiers:

```php
/**
 * @ApiResource()
 * @ApiResource(path="/countries/{countryId}/companies/{companyId}/users", identifiers={"companyId": {Company::class, "id"}, "countryId": {Country::class, "shortName"}})
 */
class Country {
  public string $shortName;
  public array $companies = [];
}
```

Get the company employees or administrators `/companies/1/administrators`:

```php
/**
 * @ApiResource()
 * @ApiResource(path="/companies/{companyId}/administrators")
 * @ApiResource(path="/companies/{companyId}/employees")
 */
class Company {
  public int $id;
  public Users $employees;
  public Users $administrators;
}
```

With explicit identifiers:

```php
/**
 * @ApiResource()
 * @ApiResource(path="/companies/{companyId}/administrators", identifiers={"companyId": {Company::class, "id"}, "*": {Company::class, "administrators"}})
 * @ApiResource(path="/companies/{companyId}/employees", identifiers={"companyId": {Company::class, "id"}, "*": {Company::class, "employees"}})
 */
class Company {
  public int $id;
  public Users $employees;
  public Users $administrators;
}
```

## TODO:

* Without explicit identifiers, how do we map `companyId` to Company->id ?
* Do we parse the path to find `administrators` and map it to the property ?
* The Tuple `identifiers={pathParameter: {Class, property}}` should be redefined / validated (and what about `*` for collection?)
