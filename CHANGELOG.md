# Changelog

## 2.5.0 beta 1

* GraphQL: Add support for custom queries and mutations
* GraphQL: Add support for custom types
* GraphQL: Better pagination support (backwards pagination)
* GraphQL: Add the concept of *stages* in the workflow of the resolvers and add the possibility to disable them with operation attributes
* GraphQL: Add GraphQL Playground besides GraphiQL and add the possibility to change the default IDE (or to disable it) for the GraphQL endpoint
* GraphQL: Add a command to print the schema in SDL
* GraphQL: Improve serialization performance by avoiding calls to the `serialize` PHP function
* GraphQL: Allow to use a search and an exist filter on the same resource
* GraphQL: Refactor the architecture of the whole system to allow the decoration of useful services (`TypeConverter` to manage custom types, `SerializerContextBuilder` to modify the (de)serialization context dynamically, etc.)

## 2.4.6

* GraphQL: Use correct resource configuration for filter arguments of nested collection
* Swagger UI: compatibility with Internet Explorer 11
* Varnish: Prevent cache miss by generating IRI for child related resources
* Messenger: Unwrap exception thrown in handler for Symfony Messenger 4.3
* Fix remaining Symfony 4.3 deprecation notices
* Prevent cloning non clonable objects in `previous_data`
* Return a 415 HTTP status code instead of a 406 one when a faulty `Content-Type` is sent
* Fix `WriteListener` trying to generate IRI for non-resources
* Allow to extract blank values from composite identifier

## 2.4.5

* Fix denormalization of a constructor argument which is a collection of non-resources
* Allow custom operations to return a different class than the expected resource class

## 2.4.4

* Store the original data in the `previous_data` request attribute, and allow to access it in security expressions using the `previous_object` variable (useful for PUT and PATCH requests)
* Fix resource inheritance handling
* Fix BC break in `AbstractItemNormalizer` introduced in 2.4
* Fix serialization when using interface as resource
* Basic compatibility with Symfony 4.3

## 2.4.3

* Doctrine: allow autowiring of filter classes
* Doctrine: don't use `fetchJoinCollection` on `Paginator` when not needed
* Doctrine: fix a BC break in `OrderFilter`
* GraphQL: input objects aren't nullable anymore (compliance with the Relay spec)
* Cache: Remove some useless purges
* Mercure: publish to Mercure using the default response format
* Mercure: use the Serializer context
* OpenAPI: fix documentation of the `PropertyFilter`
* OpenAPI: fix generation of the `servers` block (also fixes the compatibility with Postman)
* OpenAPI: skip not readable and not writable properties from the spec
* OpenAPI: add the `id` path parameter for POST item operation
* Serializer: add support for Symfony Serializer's `@SerializedName` metadata
* Metadata: `ApiResource`'s `attributes` property now defaults to `null`, as expected
* Metadata: Fix identifier support when using an interface as resource class
* Metadata: the HTTP method is now always uppercased
* Allow to disable listeners per operation (fix handling of empty request content)

    Previously, empty request content was allowed for any `POST` and `PUT` operations. This was an unsafe assumption which caused [other problems](https://github.com/api-platform/core/issues/2731).

    If you wish to allow empty request content, please add `"deserialize"=false` to the operation's attributes. For example:

    ```php
    <?php
    // api/src/Entity/Book.php

    use ApiPlatform\Core\Annotation\ApiResource;
    use App\Controller\PublishBookAction;

    /**
     * @ApiResource(
     *     itemOperations={
     *         "put_publish"={
     *             "method"="PUT",
     *             "path"="/books/{id}/publish",
     *             "controller"=PublishBookAction::class,
     *             "deserialize"=false,
     *         },
     *     },
     * )
     */
    class Book
    {
    ```

    You may also need to add `"validate"=false` if the controller result is `null` (possibly because you don't need to persist the resource).

* Return the `204` HTTP status code when the output class is set to `null`
* Be more resilient when normalizing non-resource objects
* Replace the `data` request attribute by the return of the data persister
* Fix error message in identifiers extractor
* Improve the bundle's default configuration when using `symfony/symfony` is required
* Fix the use of `MetadataAwareNameConverter` when available (configuring `name_converter: serializer.name_converter.metadata_aware` will now result in a circular reference error)

## 2.4.2

* Fix a dependency injection injection problem in `FilterEagerLoadingExtension`
* Improve performance by adding a `NoOpScalarNormalizer` handling scalar values

## 2.4.1

* Improve performance of the dev environment and deprecate the `api_platform.metadata_cache` parameter
* Fix a BC break in `SearchFilter`
* Don't send HTTP cache headers for unsuccessful responses
* GraphQL: parse input and messenger metadata on the GraphQl operation
* GraphQL: do not enable graphql when `webonyx/graphql-php` is not installed

## 2.4.0

* Listeners are now opt-in when not handling API Platform operations
* `DISTINCT` is not used when there are no joins
* Preserve manual join in FilterEagerLoadingExtension
* The `elasticsearch` attribute can be disabled resource-wise or per-operation
* The `messenger` attribute can now take the `input` string as a value (`messenger="input"`). This will use a default transformer so that the given `input` is directly sent to the messenger handler.
* The `messenger` attribute can be declared per-operation
* Mercure updates are now published after the Doctrine flush event instead of on `kernel.terminate`, so the Mercure and the Messenger integration can be used together
* Use Symfony's MetadataAwareNameConverter when available
* Change the extension's priorities (`<0`) for improved compatibility with Symfony's autoconfiguration feature. If you have custom extensions we recommend to use positive priorities.

| Service name                                               | Priority | Class                                              |
|------------------------------------------------------------|------|---------------------------------------------------------|
| api_platform.doctrine.orm.query_extension.eager_loading (collection) | -8 | ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\EagerLoadingExtension |
| api_platform.doctrine.orm.query_extension.eager_loading (item) | -8 | ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\EagerLoadingExtension |
| api_platform.doctrine.orm.query_extension.filter | -16 | ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterExtension |
| api_platform.doctrine.orm.query_extension.filter_eager_loading | -17 | ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterEagerLoadingExtension |
| api_platform.doctrine.orm.query_extension.order | -32 | ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\OrderExtension |
| api_platform.doctrine.orm.query_extension.pagination | -64 | ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension |

* Fix JSON-LD contexts when using output classes
* GraphQl: Fix pagination (the `endCursor` behavior was wrong)
* GraphQl: Improve output/input behavior
* GraphQl: Improve mutations (make the `clientMutationId` nullable and return mutation payload as an object)
* MongoDB: Fix search filter when searching by related collection id
* MongoDB: Fix numeric and range filters

## 2.4.0 beta 2

* Fix version constraints for Doctrine MongoDB ODM
* Respect `_api_respond` request attribute in the SerializeListener
* Change the normalizer's priorities (`< 0`). If you have custom normalizer we recommend to use positive priorities.

| Service name                                               | Priority | Class                                              |
|------------------------------------------------------------|------|---------------------------------------------------------|
| api_platform.hydra.normalizer.constraint_violation_list   | -780 | ApiPlatform\Core\Hydra\Serializer\ConstraintViolationListNormalizer
| api_platform.jsonapi.normalizer.constraint_violation_list | -780 | ApiPlatform\Core\JsonApi\Serializer\ConstraintViolationListNormalizer
| api_platform.problem.normalizer.constraint_violation_list | -780 | ApiPlatform\Core\Problem\Serializer\ConstraintViolationListNormalizer
| api_platform.swagger.normalizer.api_gateway               | -780 | ApiPlatform\Core\Swagger\Serializer\ApiGatewayNormalizer
| api_platform.hal.normalizer.collection                    | -790 | ApiPlatform\Core\Hal\Serializer\CollectionNormalizer
| api_platform.hydra.normalizer.collection_filters          | -790 | ApiPlatform\Core\Hydra\Serializer\CollectionFiltersNormalizer
| api_platform.jsonapi.normalizer.collection                | -790 | ApiPlatform\Core\JsonApi\Serializer\CollectionNormalizer
| api_platform.jsonapi.normalizer.error                     | -790 | ApiPlatform\Core\JsonApi\Serializer\ErrorNormalizer
| api_platform.hal.normalizer.entrypoint                    | -800 | ApiPlatform\Core\Hal\Serializer\EntrypointNormalizer
| api_platform.hydra.normalizer.documentation               | -800 | ApiPlatform\Core\Hydra\Serializer\DocumentationNormalizer
| api_platform.hydra.normalizer.entrypoint                  | -800 | ApiPlatform\Core\Hydra\Serializer\EntrypointNormalizer
| api_platform.hydra.normalizer.error                       | -800 | ApiPlatform\Core\Hydra\Serializer\ErrorNormalizer
| api_platform.jsonapi.normalizer.entrypoint                | -800 | ApiPlatform\Core\JsonApi\Serializer\EntrypointNormalizer
| api_platform.problem.normalizer.error                     | -810 | ApiPlatform\Core\Problem\Serializer\ErrorNormalizer
| serializer.normalizer.json_serializable                   | -900 | Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer
| serializer.normalizer.datetime                            | -910 | Symfony\Component\Serializer\Normalizer\DateTimeNormalizer
| serializer.normalizer.constraint_violation_list           | -915 | Symfony\Component\Serializer\Normalizer\ConstraintViolationListNormalizer
| serializer.normalizer.dateinterval                        | -915 | Symfony\Component\Serializer\Normalizer\DateIntervalNormalizer
| serializer.normalizer.data_uri                            | -920 | Symfony\Component\Serializer\Normalizer\DataUriNormalizer
| api_platform.graphql.normalizer.item                      | -922 | ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer
| api_platform.hal.normalizer.item                          | -922 | ApiPlatform\Core\Hal\Serializer\ItemNormalizer
| api_platform.jsonapi.normalizer.item                      | -922 | ApiPlatform\Core\JsonApi\Serializer\ItemNormalizer
| api_platform.jsonld.normalizer.item                       | -922 | ApiPlatform\Core\JsonLd\Serializer\ItemNormalizer
| api_platform.serializer.normalizer.item                   | -923 | ApiPlatform\Core\Serializer\ItemNormalizer
| serializer.normalizer.object                              | -1000 | Symfony\Component\Serializer\Normalizer\ObjectNormalizer

* Allow custom stylesheets to be appended or replaced in the swagger UI
* Load messenger only if available
* Fix missing metadata cache pool for Elasticsearch
* Make use of the new AdvancedNameConverterInterface interface for name converters
* Refactor input/output attributes, where these attributes now take:
  - an array specifying a class and some specific attributes (`name` and `iri` if needed)
  - a string representing the class
  - a `falsy` boolean to disable the input/output
* Introduce the DataTransformer concept to transform an input/output from/to a resource
* Api Platform normalizer is not limited to Resources anymore (you can use DTO as relations and more...)
* MongoDB: allow a `0` limit in the pagination
* Fix support of a discriminator mapping in an entity

## 2.4.0 beta 1

* MongoDB: full support
* Elasticsearch: add reading support (including pagination, sort filter and term filter)
* Mercure: automatically push updates to clients using the [Mercure](https://mercure.rocks) protocol
* CQRS support and async message handling using the Symfony Messenger Component
* OpenAPI: add support for OpenAPI v3 in addition to OpenAPI v2
* OpenAPI: support generating documentation using [ReDoc](https://github.com/Rebilly/ReDoc)
* OpenAPI: basic hypermedia hints using OpenAPI v3 links
* OpenAPI: expose the pagination controls
* Allow to use custom classes for input and output (DTO) with the `input_class` and `output_class` attributes
* Allow to disable the input or the output by setting `input_class` and `output_class` to false
* Guess and automatically set the appropriate Schema.org IRIs for common validation constraints
* Allow to set custom cache HTTP headers using the `cache_headers` attribute
* Allow to set the HTTP status code to send to the client through the `status` attribute
* Add support for the `Sunset` HTTP header using the `sunset` attribute
* Set the `Content-Location` and `Location` headers when appropriate for better RFC7231 conformance
* Display the matching data provider and data persister in the debug panel
* GraphQL: improve performance by lazy loading types
* Add the `api_persist` request attribute to enable or disable the `WriteListener`
* Allow to set a default context in all normalizers
* Permit to use a string instead of an array when there is only one serialization group
* Add support for setting relations using the constructor of the resource classes
* Automatically set a [409 Conflict](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/409) HTTP status code when an `OptimisticLockException` is thrown
* Resolve Dependency Injection Container parameters in the XML and YAML files for the resource class configuration
* `RequestAttributesExtractor` is not internal anymore and can be used in userland code
* Always use the user-defined metadata when set
* OpenAPI: add a description explaining how to use the property filter
* GraphQL: the look'n'feel of GraphiQL now match the API Platform one
* PHPStan level 6 compliance
* Add a `show_webby` configuration option to hide the spider in API docs
* Add an easter egg (find it!)

## 2.3.6

* /!\ Security: a vulnerability impacting the GraphQL subsystem was allowing users authorized to run mutations for a specific resource type, to execute it on any resource, of any type (CVE-2019-1000011)
* Fix normalization of raw collections (not API resources)
* Fix content negotiation format matching

## 2.3.5

* GraphQL: compatibility with `webonyx/graphql-php` 0.13
* OpenAPI/Swagger: expose `properties[]` as a collection parameter
* OpenAPI/Swagger: add a description for the `properties[]` filter
* OpenAPI/Swagger: Leverage advanced name converters
* JSON-LD: Prevent an error in `ItemNormalizer` when `$context['resource_class']` is not defined
* Allow to pass a the serialization group to use a string instead of as an array of one element
* Modernize the code base to use PHP 7.1 features when possible
* Bump minimal dependencies of the used Symfony components
* Improve the Packagist description

## 2.3.4

* Open API/Swagger: fix YAML export
* Open API/Swagger: Correctly expose overridden formats
* GraphQL: display the stack trace when in debug mode
* GraphQL: prevent a crash when the class name isn't provided
* Fix handling of one-to-one relations in subresources
* Fix max depth handling when eager fetching is disabled
* Compatibility with Symfony 4.2
* Prevent calling the remove method from all data persisters
* Persist Doctrine entities with the `DEFERRED_EXPLICIT` change tracking policy
* Throw an `InvalidArgumentException` when trying to get an item from a collection route
* Improve the debug bar panel visibility
* Take into account the `route_prefix` attribute in subresources
* Allow to use multiple values with `NumericFilter`
* Improve exception handling in `ReadListener` by adding the previous exception

## 2.3.3

* Doctrine: revert "prevent data duplication in Eager loaded relations"

## 2.3.2

* Open API/Swagger: detect correctly collection parameters
* Open API/Swagger: fix serialization of nested objects when exporting as YAML
* GraphQL: fix support of properties also mapped as subresources
* GraphQL: fix retrieving the internal `_id` when `id` is not part of the requested fields
* GraphQL: only exposes the mutations if any
* Doctrine: prevent data duplication in Eager loaded relations
* Preserve the host in the internal router

## 2.3.1

* Data persisters: call only the 1st matching data persister, this fix may break existing code, see https://github.com/api-platform/docs/issues/540#issuecomment-405945358
* Subresources: fix inverse side population
* Subresources: add subresources collections to cache tags
* Subresources: fix Doctrine identifier parameter type detection
* Subresources: fix max depth handling
* GraphQL: send a 200 HTTP status code when a GraphQL response contain some errors
* GraphQL: fix filters to allow dealing with multiple values
* GraphQL: remove invalid and useless parameters from the GraphQL schema
* GraphQL: use the collection resolver in mutations
* JSON API: remove duplicate data from includes
* Filters: fix composite keys support
* Filters: fix the `OrderFilter` when applied on nested entities
* List Doctrine Inflector as a hard dependency
* Various quality and usability improvements

## 2.3.0

* Add support for deprecating resources, operations and fields in GraphQL, Hydra and Swagger
* Add API Platform panels in the Symfony profiler and in the web debug toolbar
* Make resource class's constructor parameters writable
* Add support for interface as a resource
* Add a shortcut syntax to define attributes at the root of `@ApiResource` and `@ApiProperty` annotations
* Throw an exception if a required filter isn't set
* Allow to specify the message when access is denied using the `access_control_message` attribute
* Add a new option to include null results when using the date filter
* Allow data persisters to return a new instance instead of mutating the existing one
* Add a new attribute to configure specific formats per resources or operations
* Add an `--output` option to the `api:swagger:export` command
* Implement the `CacheableSupportsMethodInterface` introduced in Symfony 4.1 in all (de)normalizers (improves the performance dramatically)
* Drop support for PHP 7.0
* Upgrade Swagger UI and GraphiQL
* GraphQL: Add a `totalCount` field in GraphQL paginated collections
* JSONAPI: Allow inclusion of related resources

## 2.2.10

* /!\ Security: a vulnerability impacting the GraphQL subsystem was allowing users authorized to run mutations for a specific resource type, to execute it on any resource, of any type (CVE-2019-1000011)

## 2.2.9

* Fix `ExistsFilter` for inverse side of OneToOne association
* Fix to not populate subresource inverse side
* Improve the overall code quality (PHPStan analysis)

## 2.2.8

* Fix support for max depth when using subresources
* Fix a fatal error when a subresource type is not defined
* Add support for group sequences in the validator configuration
* Add a local class metadata cache in the HAL normalizer
* `FilterEagerLoadingExtension` now accepts joins with class name as join value

## 2.2.7

* Compatibility with Symfony 4.1
* Compatibility with webonyx/graphql-php 0.12
* Add missing `ApiPlatform\Core\EventListener\EventPriorities`'s `PRE_SERIALIZE` and `POST_SERIALIZE` constants
* Disable eager loading when no groups are specified to avoid recursive joins
* Fix embeddable entities eager loading with groups
* Don't join the same association twice when eager loading
* Fix max depth handling when using HAL
* Check the value of `enable_max_depth` if defined
* Minor performance and quality improvements

## 2.2.6

* Fix identifiers creation and update when using GraphQL
* Fix nested properties support when using filters with GraphQL
* Fix a bug preventing the `ExistFilter` to work properly with GraphQL
* Fix a bug preventing to use a custom denormalization context when using GraphQL
* Enforce the compliance with the JSONAPI spec by throwing a 400 error when using the "inclusion of related resources" feature
* Update `ChainSubresourceDataProvider` to take into account `RestrictedDataProviderInterface`
* Fix the cached identifiers extractor support for stringable identifiers
* Allow a `POST` request to have an empty body
* Fix a crash when the ExpressionLanguage component isn't installed
* Enable item route on collection's subresources
* Fix an issue with subresource filters, was incorrectly adding filters for the parent instead of the subresource
* Throw when a subresources identifier is not found
* Allow subresource items in the `IriConverter`
* Don't send the `Link` HTTP header pointing to the Hydra documentation if docs are disabled
* Fix relations denormalization with plain identifiers
* Prevent the `OrderFilter` to trigger faulty deprecation notices
* Respect the `fetchEager=false` directive on an association in the `EagerLoadingExtension`
* Use the configured name converter (if any) for relations in the HAL's `ItemNormalizer`
* Use the configured name converter (if any) in the `ConstraintViolationListNormalizer`
* Dramatically improve the overall performance by fixing the normalizer's cache key generation
* Improve the performance `CachedRouteNameResolver` and `CachedSubresourceOperationFactory` by adding a local memory cache layer
* Improve the performance of access control checking when using GraphQL
* Improve the performance by using `isResourceClass` when possible
* Remove a useless `try/catch` in the `CachedTrait`
* Forward the operation name to the `IriConverter`
* Fix some more code quality issues

## 2.2.5

* Fix a various issues preventing the metadata cache to work properly (performance fix)
* Fix a cache corruption issue when using subresources
* Fix non-standard outputs when using the HAL format
* Persist data in Doctrine DataPersister only if needed
* Fix identifiers handling in GraphQL mutations
* Fix client-side ID creation or update when using GraphQL mutations
* Fix an error that was occuring when the Expression Language component wasn't installed
* Update the `ChainSubresourceDataProvider` class to take into account `RestrictedDataProviderInterface`

## 2.2.4

* Fix a BC break preventing to pass non-arrays to the builtin Symfony normalizers when using custom normalizers
* Fix a bug when using `FilterEagerLoadingExtension` with manual joins
* Fix some bugs in the AWS API Gateway compatibility mode for Open API/Swagger

## 2.2.3

* Fix object state inconsistency after persistence
* Allow to use multiple `@ApiFilter` annotations on the same class
* Fix a BC break when the serialization context builder depends of the retrieved data
* Fix a bug regarding collections handling in the GraphQL endpoint

## 2.2.2

* Autoregister classes implementing `SubresourceDataProviderInterface`
* Fix the `DateTimeImmutable` support in the date filter
* Fix a BC break in `DocumentationAction` impacting NelmioApiDoc
* Fix the context passed to data providers (improve the eager loading)
* Fix fix a subresource's metadata cache bug
* Fix the configuration detection when using a custom directory structure

## 2.2.1

* Merge bug fixes from older branches

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
* Fix a bug occurring when passing neither an IRI nor an array in an embedded relation
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
