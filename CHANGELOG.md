# Changelog

## 2.2.1

* Autoregister classes implementing `SubresourceDataProviderInterface` 
* Fix the `DateTimeImmutable` support in the date filter
* Fix a BC break in `DocumentationAction` impacting NelmioApiDoc
* Fix the context passed to data providers (improve the eager loading)
* Fix fix a subresource's metadata cache bug
* Fix the configuration detection when using a custom directory structure

## 2.2.0

* Add GraphQL support (including mutations, pagination, filters, access control rules and automatic SQL joins)
* Fully implement the GraphQL Relay Server specification
* Add JSONAPI support
* Add a new `@ApiFilter` annotation to directly configure filters from resource classes
* Add a partial paginator that prevents `COUNT()` SQL queries
* Add a new simplified way to configure operations
* Add an option to serialize Validator's payloads (e.g. error levels)
* Add support for generators in data providers
* Add a new `allow_plain_identifiers` option to allow using plain IDs as identifier instead of IRIs
* Add support for resource names without namespace
* Automatically enable FOSUser support if the bundle is installed
* Add an `AbstractCollectionNormalizer` to help supporting custom formats
* Deprecate NelmioApiDocBundle 2 support (upgrade to v3, it has native API Platform support)
* Deprecate the `ApiPlatform\Core\Bridge\Doctrine\EventListener\WriteListener` class in favor of the new `ApiPlatform\Core\EventListener\WriteListener` class.
* Remove the `api_platform.doctrine.listener.view.write` event listener service.
* Add a data persistence layer with a new `ApiPlatform\Core\DataPersister\DataPersisterInterface` interface.
* Add the a new configuration to disable the API entrypoint and the documentation
* Allow to set maximum items per page at operation/resource level  
* Add the ability to customize the message when configuring an access control rule trough the `access_control_message` attribute
* Allow empty operations in XML configs 

## 2.1.6

* Add a new config option to specify the directories containing resource classes
* Fix a bug regarding the ordering filter when dealing with embedded fields
* Allow to autowire the router
* Fix the base path handling the Swagger/Open API documentation normalizer

## 2.1.5

* Add support for filters autoconfiguration with Symfony 3.4+
* Add service aliases required to use the autowiring with Symfony 3.4+
* Allow updating nested resource when issuing a `POST` HTTP request
* Add support for the immutable date and time types introduced in Doctrine
* Fix the Doctrine query generated to retrieve nested subresources
* Fix several bugs in the automatic eager loading support
* Fix a bug occurring when passing nor an IRI nor an array in an embedded relation
* Allow to request `0` items per page in collections
* Also copy the `Host` from the Symfony Router
* `Paginator::getLastPage()` now always returns a `float`
* Minor performance improvements
* Minor quality fixes

## 2.1.4

* Symfony 3.4 and 4.0 compatibility
* Autowiring strict mode compatibility 
* Fix a bug preventing to create resource classes in the global namespace
* Fix Doctrine type conversion in filter's WHERE clauses
* Fix filters when using eager loading and non-association composite identifier
* Fix Doctrine type resolution for identifiers (for custom DBALType) 
* Add missing Symfony Routing options to operations configuration
* Add SubresourceOperations to metadata
* Fix disabling of cache pools with the dev environment 

## 2.1.3

* Don't use dynamic values in Varnish-related service keys (improves Symfony 3.3 compatibility)
* Hydra: Fix the value of `owl:allValuesFrom` in the API documentation
* Swagger: Include the context even when the type is `null`
* Minor code and PHPDoc cleanups

## 2.1.2

* PHP 7.2 compatibility
* Symfony 4 compatibility
* Fix the Swagger UI documentation for specific routes (the API request wasn't executed automatically anymore)
* Add a missing cache tag on empty collections
* Fix a missing service when no Varnish URL is defined
* Fix the whitelist comparison in the property filer
* Fix some bugs regarding subresources in the Swagger and Hydra normalizers
* Make route requirements configurable
* Make possible to configure the Swagger context for properties
* Better exception messages when there is a content negotiation error
* Use the `PriorityTaggedServiceTrait` provided by Symfony instead of a custom implementation
* Test upstream libs deprecations
* Various quality fixes and tests cleanup

## 2.1.1

* Fix path generators
* Fix some method signatures related to subresources
* Improve performance of the deserialization mechanism

## 2.1.0

* Add a builtin HTTP cache invalidation system able to store all requests in Varnish (or any other proxy supporting cache tags) and purge it instantly when needed
* Add an authorization system configurable directly from the resource class
* Add support for subresources (like `/posts/1/comments` or `/posts/1/comments/2`
* Revamp the automatic documentation UI (upgraded to the React-based version of Swagger UI, added a custom stylesheet)
* Add a new filter to select explicitly which properties to serialize
* Add a new filter to choose which serialization group to apply
* Add a new filter to test if a property value exists or not
* Add support for OAuth 2 in the UI
* Add support for embedded fields
* Add support for customizable API resources folder's name
* Filters's ids now defaults to the Symfony's service name
* Add configuration option to define custom metadata loader paths
* Make Swagger UI compatible with a strict CSP environment
* Add nulls comparison to OrderFilter
* Add a flag to disable all request listeners
* Add a default order option in the configuration
* Allow to disable all operations using the XML configuration format and deprecate the previous format
* Allow upper cased property names
* Improve the overall performance by optimizing `RequestAttributesExtractor`
* Improve the performance of the filters subsystem by using a PSR-11 service locator and deprecate the `FilterCollection` class
* Add compatibility with Symfony Flex and Symfony 4
* Allow the Symfony Dependency Injection component to autoconfigure data providers and query extensions
* Allow to use service for dynamic validation groups
* Allow using PHP constants in YAML resources files
* Upgrade to the latest version of the Hydra spec
* Add `pagination` and `itemPerPage` parameters in the Swagger/Open API documentation
* Add support for API key authentication in Swagger UI
* Allow to specify a whitelist of serialization groups
* Allow to use the new immutable date and time types of Doctrine in filters
* Update swagger definition keys to more verbose ones (ie `Resource-md5($groups)` => `Resource-groupa_groupb`) - see https://github.com/api-platform/core/pull/1207

## 2.0.11

* Ensure PHP 7.2 compatibility
* Fix some bug regarding Doctrine joins
* Let the `hydra_context` option take precedence over operation metadata
* Fix relations handling by the non-hypermedia `ItemNormalizer` (raw JSON, XML)
* Fix a bug in the JSON-LD context: should not be prefixed by `#`
* Fix a bug regarding serialization groups in Hydra docs

## 2.0.10

* Performance improvement
* Swagger: Allow non-numeric IDs (such as UUIDs) in URLs
* Fix a bug when a composite identifier is missing
* `ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter::extractProperties` now always return an array
* Fix NelmioApiDocParser recursive relations

## 2.0.9

* Add support for Symfony 3.3
* Disable the partial eager loading by default
* Fix support for ignored attributes in normalizers
* Specify the `LEFT JOIN` clause for filter associations
* Move the metadata from validator factory to the validator.xml file
* Throw an exception when the number of items per page is 0
* Improve the Continuous Integration process

## 2.0.8

* Leverage serialization groups to eager load data
* Fix the Swagger Normalizer to correctly support nested serialization groups
* Use strict types
* Get rid of the dependency to the Templating component
* Explicitly add missing dependency to PropertyAccess component
* Allow the operation name to be null in ResourceMetadata
* Fix an undefined index error occurring in some cases when using sub types
* Make the bundle working even when soft dependencies aren't installed
* Fix serialization of multiple inheritance child types
* Fix the priority of the FOSUSer's event listener
* Fix the resource class resolver with using `\Traversable` values
* Fix inheritance of property metadata for the Doctrine ORM property metadata factory
* EagerLoadingExtension: Disable partial fetching if entity has subclasses
* Refactoring and cleanup of the eager loading mechanism
* Fix the handling of composite identifiers
* Fix HAL normalizer when the context isn't serializable
* Fix some quality problems found by PHPStan

## 2.0.7

* [security] Hide error's message in prod mode when a 500 error occurs (Api Problem format)
* Fix sorting when eager loading is used
* Allow eager loading when using composite identifiers
* Don't use automatic eager loading when disabled in the config
* Use `declare(strict_types=1)` and improve coding standards
* Automatically refresh routes in dev mode when a resource is created or deleted

## 2.0.6

* Correct the XML Schema type generated for floats in the Hydra documentation

## 2.0.5

* Fix a bug when multiple filters are applied

## 2.0.4

* [security] Hide error's message in prod mode when a 500 error occurs
* Prevent duplicate data validation
* Fix filter Eager Loading
* Fix the Hydra documentation for `ConstraintViolationList`
* Fix some edge cases with the automatic configuration of Symfony
* Remove calls to `each()` (deprecated since PHP 7.2)
* Add a missing property in `EagerLoadingExtension`

## 2.0.3

* Fix a bug when handling invalid IRIs
* Allow to have a property called id even in JSON-LD
* Exclude static methods from AnnotationPropertyNameCollectionFactory
* Improve compatibility with Symfony 2.8

## 2.0.2

* Fix the support of the Symfony's serializer @MaxDepth annotation
* Fix property range of relations in the Hydra doc when an IRI is used
* Fix an error "api:swagger:export" command when decorating the Swagger normalizer
* Fix an an error in the Swagger documentation generator when a property has several serialization groups

## 2.0.1

* Various fixes related to automatic eager loading
* Symfony 3.2 compatibility

## 2.0.0

* Full refactoring
* Use PHP 7
* Add support for content negotiation
* Add Swagger/OpenAPI support
* Integrate Swagger UI
* Add HAL support
* Add API Problem support
* Update the Hydra support to be in sync with the last version of the spec
* Full rewrite of the metadata system (annotations, YAML and XML formats support)
* Remove the event system in favor of the builtin Symfony kernel's events
* Use the ADR pattern
* Fix a ton of issues
* `ItemDataproviderInterface`: `fetchData` is now in the context parameterer. `getItemFromIri` is now context aware [7f82fd7](https://github.com/api-platform/core/commit/7f82fd7f96bbb855599de275ffe940c63156fc5d)
* Constants for event's priorities [2e7b73e](https://github.com/api-platform/core/commit/2e7b73e19ccbeeb8387fa7c4f2282984d4326c1f)
* Properties mapping with XML/YAML is now possible [ef5d037](https://github.com/api-platform/core/commit/ef5d03741523e35bcecc48decbb92cd7b310a779)
* Ability to configure and match exceptions with an HTTP status code [e9c1863](https://github.com/api-platform/core/commit/e9c1863164394607f262d975e0f00d51a2ac5a72)
* Various fixes and improvements (SwaggerUI, filters, stricter property metadata)

## 1.1.1

* Fix a case typo in a namespace alias in the Hydra documentation

## 1.1.0 beta 2

* Allow to configure the default controller to use
* Ability to add route requirements
* Add a range filter
* Search filter: add a case sensitivity setting
* Search filter: fix the behavior of the search filter when 0 is provided as value
* Search filter: allow to use identifiers different than id
* Exclude tests from classmap
* Fix some deprecations and tests

## 1.1.0 beta 1

* Support Symfony 3.0
* Support nested properties in Doctrine filters
* Add new `start` and `word_start` strategies to the Doctrine Search filter
* Add support for abstract resources
* Add a new option to totally disable Doctrine
* Remove the ID attribute from the Hydra documentation when it is read only
* Add method to avoid naming collision of DQL join alias and bound parameter name
* Make exception available in the Symfony Debug Toolbar
* Improve the Doctrine Paginator performance in some cases
* Enhance HTTPS support and fix some bugs in the router
* Fix some edge cases in the date and time normalizer
* Propagate denormalization groups through relations
* Run tests against all supported Symfony versions
* Add a contribution documentation
* Refactor tests
* Check CS with StyleCI

## 1.0.1

* Avoid an error if the attribute isn't an array

## 1.0.0

* Extract the documentation in a separate repository
* Add support for eager loading in collections

## 1.0.0 beta 3

* The Hydra documentation URL is now `/apidoc` (was `/vocab`)
* Exceptions implements `Dunglas\ApiBundle\Exception\ExceptionInterface`
* Prefix automatically generated route names by `api_`
* Automatic detection of the method of the entity class returning the identifier when using Doctrine (previously `getId()` was always used)
* New extension point in `Dunglas\ApiBundle\Doctrine\Orm\DataProvider` allowing to customize Doctrine paginator and performance optimization when using typical queries
* New `Dunglas\ApiBundle\JsonLd\Event\Events::CONTEXT_BUILDER` event allowing to modify the JSON-LD context
* Change HTTP status code from `202` to `200` for `PUT` requests
* Ability to embed the JSON-LD context instead of embedding it

## 1.0.0 beta 2

* Preserve indexes when normalizing and denormalizing associative arrays
* Allow to set default order for property when registering a `Doctrine\Orm\Filter\OrderFilter` instance
