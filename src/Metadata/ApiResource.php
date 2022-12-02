<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Metadata;

use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\State\OptionsInterface;

/**
 * Resource metadata attribute.
 *
 * The API Resource attribute declares the behaviors attached to a Resource inside API Platform.
 * This class is immutable, and if you set a value yourself, API Platform will not override the value.
 * The API Resource helps sharing options with operations.
 *
 * Read more about how metadata works [here](/docs/in-depth/metadata).
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class ApiResource
{
    use WithResourceTrait;

    protected ?Operations $operations;

    /**
     * @var string|callable|null
     */
    protected $provider;

    /**
     * @var string|callable|null
     */
    protected $processor;

    /**
     * @param array<int, HttpOperation>|array<string, HttpOperation>|Operations|null $operations   Operations is a list of HttpOperation
     * @param array<string, Link>|array<string, mixed[]>|string[]|string|null        $uriVariables
     * @param string|callable|null                                                   $provider
     * @param string|callable|null                                                   $processor
     * @param mixed|null                                                             $mercure
     * @param mixed|null                                                             $messenger
     * @param mixed|null                                                             $input
     * @param mixed|null                                                             $output
     */
    public function __construct(
        /**
         * The URI template represents your resource IRI with optional variables. It follows [RFC 6570](https://www.rfc-editor.org/rfc/rfc6570.html).
         * API Platform generates this URL for you if you leave this empty.
         */
        protected ?string $uriTemplate = null,

        /**
         * The short name of your resource is a unique name that identifies your resource.
         * It is used within the documentation and for url generation if the `uriTemplate` is not filled. By default, this will be the name of your PHP class.
         */
        protected ?string $shortName = null,

        /**
         * A description for this resource that will show on documentations.
         */
        protected ?string $description = null,

        /**
         * The RDF types of this resource.
         * An RDF type is usually a URI referencing how your resource is structured for the outside world. Values can be a string `https://schema.org/Book`
         * or an array of string `['https://schema.org/Flight', 'https://schema.org/BusTrip']`.
         */
        protected string|array|null $types = null,

        /**
         * Operations is a list of [HttpOperation](./HttpOperation).
         *
         * By default API Platform declares operations representing CRUD routes if you don't specify this parameter:
         *
         * ```php
         * #[ApiResource(
         *     operations: [
         *         new Get(uriTemplate: '/books/{id}'),
         *         // The GetCollection operation returns a list of Books.
         *         new GetCollection(uriTemplate: '/books'),
         *         new Post(uriTemplate: '/books'),
         *         new Patch(uriTemplate: '/books/{id}'),
         *         new Delete(uriTemplate: '/books/{id}'),
         *     ]
         * )]
         *
         * ```
         *
         * Try this live at [play.api-platform.com/api-resource](play.api-platform.com).
         */
        $operations = null,

        /**
         * The `formats` option allows you to customize content negotiation. By default API Platform supports JsonLd, Hal, JsonAPI.
         * For other formats we use the Symfony Serializer.
         *
         * ```php
         * #[ApiResource(
         *   formats: [
         *       'jsonld' => ['application/ld+json'],
         *       'jsonhal' => ['application/hal+json'],
         *       'jsonapi' => ['application/vnd.api+json'],
         *       'json' =>    ['application/json'],
         *       'xml' =>     ['application/xml', 'text/xml'],
         *       'yaml' =>    ['application/x-yaml'],
         *       'csv' =>     ['text/csv'],
         *       'html' =>    ['text/html'],
         *       'myformat' =>['application/vnd.myformat'],
         *   ]
         * )]
         * ```
         *
         * Learn more about custom formats in the [dedicated guide](/guides/custom-formats).
         */
        protected array|string|null $formats = null,
        /**
         * The `inputFormats` option allows you to customize content negotiation for HTTP bodies:.
         *
         * ```php
         *  #[ApiResource(formats: ['jsonld', 'csv' => ['text/csv']], operations: [
         *      new Patch(inputFormats: ['json' => ['application/merge-patch+json']]),
         *      new GetCollection(),
         *      new Post(),
         *  ])]
         * ```
         */
        protected array|string|null $inputFormats = null,
        /**
         * The `outputFormats` option allows you to customize content negotiation for HTTP responses.
         */
        protected array|string|null $outputFormats = null,
        /**
         * The `uriVariables` configuration allows to configure to what each URI Variable.
         * With [simple string expansion](https://www.rfc-editor.org/rfc/rfc6570.html#section-3.2.2), we read the input
         * value and match this to the given `Link`. Note that this setting is usually used on an operation directly:.
         *
         * ```php
         *   #[ApiResource(
         *       uriTemplate: '/companies/{companyId}/employees/{id}',
         *       uriVariables: [
         *           'companyId' => new Link(fromClass: Company::class, toProperty: 'company']),
         *           'id' => new Link(fromClass: Employee::class)
         *       ],
         *       operations: [new Get()]
         *   )]
         * ```
         *
         * For more examples, read our guide on [subresources](/guides/subresources).
         */
        protected $uriVariables = null,
        /**
         * The `routePrefix` allows you to configure a prefix that will apply to this resource.
         *
         * ```php
         *   #[ApiResource(
         *       routePrefix: '/books',
         *       operations: [new Get(uriTemplate: '/{id}')]
         *   )]
         * ```
         *
         * This resource will be accessible through `/books/{id}`.
         */
        protected ?string $routePrefix = null,
        /**
         * The `defaults` option adds up to [Symfony's route defaults](https://github.com/symfony/routing/blob/8f068b792e515b25e26855ac8dc7fe800399f3e5/Route.php#L41). You can override [API Platform's defaults](https://github.com/api-platform/core/blob/6abd0fe0a69d4842eb6d5c31ef2bd6dce0e1d372/src/Symfony/Routing/ApiLoader.php#L87) if needed.
         */
        protected ?array $defaults = null,
        /**
         * The `requirements` option configures the Symfony's Route requirements.
         */
        protected ?array $requirements = null,
        /**
         * The `options` option configures the Symfony's Route options.
         */
        protected ?array $options = null,
        /**
         * The `stateless` option configures the Symfony's Route stateless option.
         */
        protected ?bool $stateless = null,
        /**
         * The `sunset` option indicates when a deprecated operation will be removed.
         *
         * <CodeSelector>
         *
         * ```php
         * <?php
         * // api/src/Entity/Parchment.php
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource(deprecationReason: 'Create a Book instead', sunset: '01/01/2020')]
         * class Parchment
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Parchment:
         *         - deprecationReason: 'Create a Book instead'
         *           sunset: '01/01/2020'
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         *
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Parchment" deprecationReason="Create a Book instead" sunset="01/01/2020" />
         * </resources>
         * ```
         *
         * </CodeSelector>
         */
        protected ?string $sunset = null,
        protected ?string $acceptPatch = null,
        protected ?int $status = null,
        protected ?string $host = null,
        protected ?array $schemes = null,
        protected ?string $condition = null,
        protected ?string $controller = null,
        protected ?string $class = null,
        /**
         * The `urlGenerationStrategy` option configures the url generation strategy.
         *
         * See: [UrlGeneratorInterface::class](/reference/Api/UrlGeneratorInterface)
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\ApiResource;
         * use ApiPlatform\Api\UrlGeneratorInterface;
         *
         * #[ApiResource(urlGenerationStrategy: UrlGeneratorInterface::ABS_URL)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * App\Entity\Book:
         *     urlGenerationStrategy: !php/const ApiPlatform\Api\UrlGeneratorInterface::ABS_URL
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         *
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book" urlGenerationStrategy="0" />
         * </resources>
         * ```
         * </CodeSelector>
         */
        protected ?int $urlGenerationStrategy = null,
        /**
         * The `deprecationReason` option deprecates the current resource with a deprecation message.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Parchment.php
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource(deprecationReason: 'Create a Book instead')]
         * class Parchment
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Parchment:
         *         - deprecationReason: 'Create a Book instead'
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         *
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Parchment" deprecationReason="Create a Book instead" />
         * </resources>
         * ```
         * </CodeSelector>
         *
         * - With JSON-lD / Hydra, [an `owl:deprecated` annotation property](https://www.w3.org/TR/owl2-syntax/#Annotation_Properties) will be added to the appropriate data structure
         * - With Swagger / OpenAPI, [a `deprecated` property](https://swagger.io/docs/specification/2-0/paths-and-operations/) will be added
         * - With GraphQL, the [`isDeprecated` and `deprecationReason` properties](https://facebook.github.io/graphql/June2018/#sec-Deprecation) will be added to the schema
         */
        protected ?string $deprecationReason = null,
        protected ?array $cacheHeaders = null,
        protected ?array $normalizationContext = null,
        protected ?array $denormalizationContext = null,
        protected ?bool $collectDenormalizationErrors = null,
        protected ?array $hydraContext = null,
        protected ?array $openapiContext = null, // TODO Remove in 4.0
        protected bool|OpenApiOperation|null $openapi = null,
        /**
         * The `validationContext` option configures the context of validation for the current ApiResource.
         * You can, for instance, describe the validation groups that will be used:.
         *
         * ```php
         * #[ApiResource(validationContext: ['groups' => ['a', 'b']])]
         * ```
         *
         * For more examples, read our guide on [validation](/guides/validation).
         */
        protected ?array $validationContext = null,
        /**
         * The `filters` option configures the filters (declared as services) available on the collection routes for the current resource.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource(filters: ['app.filters.book.search'])]
         * class Book
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Book:
         *         - filters: ['app.filters.book.search']
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book">
         *         <filters>
         *             <filter>app.filters.book.search</filter>
         *         </filters>
         *     </resource>
         * </resources>
         * ```
         * </CodeSelector>
         */
        protected ?array $filters = null,
        protected ?bool $elasticsearch = null,
        protected $mercure = null,
        /**
         * The `messenger` option dispatches the current resource through the Message Bus.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource(messenger: true)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Book:
         *         - messenger: true
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         *
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book" messenger=true />
         * </resources>
         * ```
         * </CodeSelector>
         *
         * Note: when using `messenger=true` on a Doctrine entity, the Doctrine Processor is not called. If you want it
         * to be called, you should [decorate a built-in state processor](/docs/guide/hook-a-persistence-layer-with-a-processor)
         * and implement your own logic.
         *
         * Read [how to use Messenger with an Input object](/docs/guide/using-messenger-with-an-input-object).
         *
         * @var string|bool|null
         */
        protected $messenger = null,
        protected $input = null,
        protected $output = null,
        /**
         * Override the default order of items in your collection. Note that this is handled by our doctrine filters such as
         * the [OrderFilter](/docs/reference/Doctrine/Orm/Filter/OrderFilter).
         *
         * By default, items in the collection are ordered in ascending (ASC) order by their resource identifier(s). If you want to
         * customize this order, you must add an `order` attribute on your ApiResource annotation:
         *
         * <CodeSelector>
         *
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * namespace App\Entity;
         *
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource(order: ['foo' => 'ASC'])]
         * class Book
         * {
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources/Book.yaml
         * App\Entity\Book:
         *     order:
         *         foo: ASC
         * ```
         *
         * </CodeSelector>
         *
         * This `order` attribute is used as an array: the key defines the order field, the values defines the direction.
         * If you only specify the key, `ASC` direction will be used as default.
         */
        protected ?array $order = null,
        protected ?bool $fetchPartial = null,
        protected ?bool $forceEager = null,
        /**
         * The `paginationClientEnabled` option allows (or disallows) the client to enable (or disable) the pagination for the current resource.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource(paginationClientEnabled: true)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Book:
         *         - paginationClientEnabled: true
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         *
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book" paginationClientEnabled=true />
         * </resources>
         * ```
         * </CodeSelector>
         *
         * The pagination can now be enabled (or disabled) by adding a query parameter named `pagination`:
         * - `GET /books?pagination=false`: disabled
         * - `GET /books?pagination=true`: enabled
         */
        protected ?bool $paginationClientEnabled = null,
        /**
         * The `paginationClientItemsPerPage` option allows (or disallows) the client to set the number of items per page for the current resource.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource(paginationClientItemsPerPage: true)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Book:
         *         - paginationClientItemsPerPage: true
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         *
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book" paginationClientItemsPerPage=true />
         * </resources>
         * ```
         * </CodeSelector>
         *
         * The number of items can now be set by adding a query parameter named `itemsPerPage`:
         * - `GET /books?itemsPerPage=50`
         */
        protected ?bool $paginationClientItemsPerPage = null,
        /**
         * The `paginationClientPartial` option allows (or disallows) the client to enable (or disable) the partial pagination for the current resource.
         *
         * <CodeSelector>
         *
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource(paginationClientPartial: true)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Book:
         *         - paginationClientPartial: true
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         *
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book" paginationClientPartial=true />
         * </resources>
         * ```
         * </CodeSelector>
         *
         * The partial pagination can now be enabled (or disabled) by adding a query parameter named `partial`:
         * - `GET /books?partial=false`: disabled
         * - `GET /books?partial=true`: enabled
         */
        protected ?bool $paginationClientPartial = null,
        /**
         * The `paginationViaCursor` option configures the cursor-based pagination for the current resource.
         * Select your unique sorted field as well as the direction you'll like the pagination to go via filters.
         * Note that for now you have to declare a `RangeFilter` and an `OrderFilter` on the property used for the cursor-based pagination:.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\ApiFilter;
         * use ApiPlatform\Metadata\ApiResource;
         * use ApiPlatform\Doctrine\Odm\Filter\OrderFilter;
         * use ApiPlatform\Doctrine\Odm\Filter\RangeFilter;
         *
         * #[ApiResource(paginationPartial: true, paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']])]
         * #[ApiFilter(RangeFilter::class, properties: ["id"])]
         * #[ApiFilter(OrderFilter::class, properties: ["id" => "DESC"])]
         * class Book
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Book:
         *         - paginationPartial: true
         *           paginationViaCursor:
         *               - { field: 'id', direction: 'DESC' }
         *           filters: [ 'app.filters.book.range', 'app.filters.book.order' ]
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         *
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book" paginationPartial=true>
         *         <filters>
         *             <filter>app.filters.book.range</filter>
         *             <filter>app.filters.book.order</filter>
         *         </filters>
         *         <paginationViaCursor>
         *             <paginationField field="id" direction="DESC" />
         *         </paginationViaCursor>
         *     </resource>
         * </resources>
         * ```
         * </CodeSelector>
         *
         * To know more about cursor-based pagination take a look at [this blog post on medium (draft)](https://medium.com/@sroze/74fd1d324723).
         */
        protected ?array $paginationViaCursor = null,
        /**
         * The `paginationEnabled` option enables (or disables) the pagination for the current resource.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource(paginationEnabled: true)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Book:
         *         - paginationEnabled: true
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         *
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book" paginationEnabled=true />
         * </resources>
         * ```
         * </CodeSelector>
         */
        protected ?bool $paginationEnabled = null,
        /**
         * The PaginationExtension of API Platform performs some checks on the `QueryBuilder` to guess, in most common
         * cases, the correct values to use when configuring the Doctrine ORM Paginator: `$fetchJoinCollection`
         * argument, whether there is a join to a collection-valued association.
         *
         * When set to `true`, the Doctrine ORM Paginator will perform an additional query, in order to get the
         * correct number of results. You can configure this using the `paginationFetchJoinCollection` option:
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource(paginationFetchJoinCollection: false)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Book:
         *         - paginationFetchJoinCollection: false
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         *
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book" paginationFetchJoinCollection=false />
         * </resources>
         * ```
         * </CodeSelector>
         *
         * For more information, please see the [Pagination](https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/pagination.html) entry in the Doctrine ORM documentation.
         */
        protected ?bool $paginationFetchJoinCollection = null,
        /**
         * The PaginationExtension of API Platform performs some checks on the `QueryBuilder` to guess, in most common
         * cases, the correct values to use when configuring the Doctrine ORM Paginator: `$setUseOutputWalkers` setter,
         * whether to use output walkers.
         *
         * When set to `true`, the Doctrine ORM Paginator will use output walkers, which are compulsory for some types
         * of queries. You can configure this using the `paginationUseOutputWalkers` option:
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource(paginationUseOutputWalkers: false)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Book:
         *         - paginationUseOutputWalkers: false
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book" paginationUseOutputWalkers=false />
         * </resources>
         * ```
         * </CodeSelector>
         *
         * For more information, please see the [Pagination](https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/pagination.html) entry in the Doctrine ORM documentation.
         */
        protected ?bool $paginationUseOutputWalkers = null,
        /**
         * The `paginationItemsPerPage` option defines the number of items per page for the current resource.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource(paginationItemsPerPage: 30)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Book:
         *         - paginationItemsPerPage: 30
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book" paginationItemsPerPage=30 />
         * </resources>
         * ```
         * </CodeSelector>
         */
        protected ?int $paginationItemsPerPage = null,
        /**
         * The `paginationMaximumItemsPerPage` option defines the maximum number of items per page for the current resource.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource(paginationMaximumItemsPerPage: 50)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Book:
         *         - paginationMaximumItemsPerPage: 50
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book" paginationMaximumItemsPerPage=50 />
         * </resources>
         * ```
         *
         * </CodeSelector>
         */
        protected ?int $paginationMaximumItemsPerPage = null,
        /**
         * The `paginationPartial` option enables (or disables) the partial pagination for the current resource.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource(paginationPartial: true)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Book:
         *         - paginationPartial: true
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book" paginationPartial=true />
         * </resources>
         * ```
         * </CodeSelector>
         */
        protected ?bool $paginationPartial = null,
        /**
         * The `paginationType` option defines the type of pagination (`page` or `cursor`) to use for the current resource.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\ApiResource;
         *
         * #[ApiResource(paginationType: 'page')]
         * class Book
         * {
         *     // ...
         * }
         * ```
         *
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * resources:
         *     App\Entity\Book:
         *         - paginationType: page
         * ```
         *
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book" paginationType="page" />
         * </resources>
         * ```
         * </CodeSelector>
         */
        protected ?string $paginationType = null,
        protected ?string $security = null,
        protected ?string $securityMessage = null,
        protected ?string $securityPostDenormalize = null,
        protected ?string $securityPostDenormalizeMessage = null,
        protected ?string $securityPostValidation = null,
        protected ?string $securityPostValidationMessage = null,
        protected ?bool $compositeIdentifier = null,
        protected ?array $exceptionToStatus = null,
        protected ?bool $queryParameterValidationEnabled = null,
        protected ?array $graphQlOperations = null,
        $provider = null,
        $processor = null,
        protected ?OptionsInterface $stateOptions = null,
        protected array $extraProperties = [],
    ) {
        $this->operations = null === $operations ? null : new Operations($operations);
        $this->provider = $provider;
        $this->processor = $processor;
        if (\is_string($types)) {
            $this->types = (array) $types;
        }
    }

    public function getOperations(): ?Operations
    {
        return $this->operations;
    }

    public function withOperations(Operations $operations): self
    {
        $self = clone $this;
        $self->operations = $operations;

        return $self;
    }

    public function getUriTemplate(): ?string
    {
        return $this->uriTemplate;
    }

    public function withUriTemplate(string $uriTemplate): self
    {
        $self = clone $this;
        $self->uriTemplate = $uriTemplate;

        return $self;
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function withShortName(string $shortName): self
    {
        $self = clone $this;
        $self->shortName = $shortName;

        return $self;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function withDescription(string $description): self
    {
        $self = clone $this;
        $self->description = $description;

        return $self;
    }

    public function getTypes(): ?array
    {
        return $this->types;
    }

    /**
     * @param string[]|string $types
     */
    public function withTypes(array|string $types): self
    {
        $self = clone $this;
        $self->types = (array) $types;

        return $self;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getFormats()
    {
        return $this->formats;
    }

    public function withFormats(mixed $formats): self
    {
        $self = clone $this;
        $self->formats = $formats;

        return $self;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getInputFormats()
    {
        return $this->inputFormats;
    }

    /**
     * @param mixed|null $inputFormats
     */
    public function withInputFormats($inputFormats): self
    {
        $self = clone $this;
        $self->inputFormats = $inputFormats;

        return $self;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getOutputFormats()
    {
        return $this->outputFormats;
    }

    /**
     * @param mixed|null $outputFormats
     */
    public function withOutputFormats($outputFormats): self
    {
        $self = clone $this;
        $self->outputFormats = $outputFormats;

        return $self;
    }

    /**
     * @return array<string, Link>|array<string, array>|string[]|string|null
     */
    public function getUriVariables()
    {
        return $this->uriVariables;
    }

    /**
     * @param array<string, Link>|array<string, array>|string[]|string|null $uriVariables
     */
    public function withUriVariables($uriVariables): self
    {
        $self = clone $this;
        $self->uriVariables = $uriVariables;

        return $self;
    }

    public function getRoutePrefix(): ?string
    {
        return $this->routePrefix;
    }

    public function withRoutePrefix(string $routePrefix): self
    {
        $self = clone $this;
        $self->routePrefix = $routePrefix;

        return $self;
    }

    public function getDefaults(): ?array
    {
        return $this->defaults;
    }

    public function withDefaults(array $defaults): self
    {
        $self = clone $this;
        $self->defaults = $defaults;

        return $self;
    }

    public function getRequirements(): ?array
    {
        return $this->requirements;
    }

    public function withRequirements(array $requirements): self
    {
        $self = clone $this;
        $self->requirements = $requirements;

        return $self;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function withOptions(array $options): self
    {
        $self = clone $this;
        $self->options = $options;

        return $self;
    }

    public function getStateless(): ?bool
    {
        return $this->stateless;
    }

    public function withStateless(bool $stateless): self
    {
        $self = clone $this;
        $self->stateless = $stateless;

        return $self;
    }

    public function getSunset(): ?string
    {
        return $this->sunset;
    }

    public function withSunset(string $sunset): self
    {
        $self = clone $this;
        $self->sunset = $sunset;

        return $self;
    }

    public function getAcceptPatch(): ?string
    {
        return $this->acceptPatch;
    }

    public function withAcceptPatch(string $acceptPatch): self
    {
        $self = clone $this;
        $self->acceptPatch = $acceptPatch;

        return $self;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function withStatus($status): self
    {
        $self = clone $this;
        $self->status = $status;

        return $self;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function withHost(string $host): self
    {
        $self = clone $this;
        $self->host = $host;

        return $self;
    }

    public function getSchemes(): ?array
    {
        return $this->schemes;
    }

    public function withSchemes(array $schemes): self
    {
        $self = clone $this;
        $self->schemes = $schemes;

        return $self;
    }

    public function getCondition(): ?string
    {
        return $this->condition;
    }

    public function withCondition(string $condition): self
    {
        $self = clone $this;
        $self->condition = $condition;

        return $self;
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function withController(string $controller): self
    {
        $self = clone $this;
        $self->controller = $controller;

        return $self;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function withClass(string $class): self
    {
        $self = clone $this;
        $self->class = $class;

        return $self;
    }

    public function getUrlGenerationStrategy(): ?int
    {
        return $this->urlGenerationStrategy;
    }

    public function withUrlGenerationStrategy(int $urlGenerationStrategy): self
    {
        $self = clone $this;
        $self->urlGenerationStrategy = $urlGenerationStrategy;

        return $self;
    }

    public function getDeprecationReason(): ?string
    {
        return $this->deprecationReason;
    }

    public function withDeprecationReason(string $deprecationReason): self
    {
        $self = clone $this;
        $self->deprecationReason = $deprecationReason;

        return $self;
    }

    public function getCacheHeaders(): ?array
    {
        return $this->cacheHeaders;
    }

    public function withCacheHeaders(array $cacheHeaders): self
    {
        $self = clone $this;
        $self->cacheHeaders = $cacheHeaders;

        return $self;
    }

    public function getNormalizationContext(): ?array
    {
        return $this->normalizationContext;
    }

    public function withNormalizationContext(array $normalizationContext): self
    {
        $self = clone $this;
        $self->normalizationContext = $normalizationContext;

        return $self;
    }

    public function getDenormalizationContext(): ?array
    {
        return $this->denormalizationContext;
    }

    public function withDenormalizationContext(array $denormalizationContext): self
    {
        $self = clone $this;
        $self->denormalizationContext = $denormalizationContext;

        return $self;
    }

    public function getCollectDenormalizationErrors(): ?bool
    {
        return $this->collectDenormalizationErrors;
    }

    public function withCollectDenormalizationErrors(bool $collectDenormalizationErrors = null): self
    {
        $self = clone $this;
        $self->collectDenormalizationErrors = $collectDenormalizationErrors;

        return $self;
    }

    /**
     * @return string[]|null
     */
    public function getHydraContext(): ?array
    {
        return $this->hydraContext;
    }

    public function withHydraContext(array $hydraContext): self
    {
        $self = clone $this;
        $self->hydraContext = $hydraContext;

        return $self;
    }

    /**
     * TODO Remove in 4.0.
     *
     * @deprecated
     */
    public function getOpenapiContext(): ?array
    {
        return $this->openapiContext;
    }

    /**
     * TODO Remove in 4.0.
     *
     * @deprecated
     */
    public function withOpenapiContext(array $openapiContext): self
    {
        $self = clone $this;
        $self->openapiContext = $openapiContext;

        return $self;
    }

    public function getOpenapi(): bool|OpenApiOperation|null
    {
        return $this->openapi;
    }

    public function withOpenapi(bool|OpenApiOperation $openapi): self
    {
        $self = clone $this;
        $self->openapi = $openapi;

        return $self;
    }

    public function getValidationContext(): ?array
    {
        return $this->validationContext;
    }

    public function withValidationContext(array $validationContext): self
    {
        $self = clone $this;
        $self->validationContext = $validationContext;

        return $self;
    }

    /**
     * @return string[]|null
     */
    public function getFilters(): ?array
    {
        return $this->filters;
    }

    public function withFilters(array $filters): self
    {
        $self = clone $this;
        $self->filters = $filters;

        return $self;
    }

    /**
     * @deprecated this will be removed in v4
     */
    public function getElasticsearch(): ?bool
    {
        return $this->elasticsearch;
    }

    /**
     * @deprecated this will be removed in v4
     */
    public function withElasticsearch(bool $elasticsearch): self
    {
        $self = clone $this;
        $self->elasticsearch = $elasticsearch;

        return $self;
    }

    /**
     * @return array|bool|mixed|null
     */
    public function getMercure()
    {
        return $this->mercure;
    }

    public function withMercure($mercure): self
    {
        $self = clone $this;
        $self->mercure = $mercure;

        return $self;
    }

    public function getMessenger()
    {
        return $this->messenger;
    }

    public function withMessenger($messenger): self
    {
        $self = clone $this;
        $self->messenger = $messenger;

        return $self;
    }

    public function getInput()
    {
        return $this->input;
    }

    public function withInput($input): self
    {
        $self = clone $this;
        $self->input = $input;

        return $self;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function withOutput($output): self
    {
        $self = clone $this;
        $self->output = $output;

        return $self;
    }

    public function getOrder(): ?array
    {
        return $this->order;
    }

    public function withOrder(array $order): self
    {
        $self = clone $this;
        $self->order = $order;

        return $self;
    }

    public function getFetchPartial(): ?bool
    {
        return $this->fetchPartial;
    }

    public function withFetchPartial(bool $fetchPartial): self
    {
        $self = clone $this;
        $self->fetchPartial = $fetchPartial;

        return $self;
    }

    public function getForceEager(): ?bool
    {
        return $this->forceEager;
    }

    public function withForceEager(bool $forceEager): self
    {
        $self = clone $this;
        $self->forceEager = $forceEager;

        return $self;
    }

    public function getPaginationClientEnabled(): ?bool
    {
        return $this->paginationClientEnabled;
    }

    public function withPaginationClientEnabled(bool $paginationClientEnabled): self
    {
        $self = clone $this;
        $self->paginationClientEnabled = $paginationClientEnabled;

        return $self;
    }

    public function getPaginationClientItemsPerPage(): ?bool
    {
        return $this->paginationClientItemsPerPage;
    }

    public function withPaginationClientItemsPerPage(bool $paginationClientItemsPerPage): self
    {
        $self = clone $this;
        $self->paginationClientItemsPerPage = $paginationClientItemsPerPage;

        return $self;
    }

    public function getPaginationClientPartial(): ?bool
    {
        return $this->paginationClientPartial;
    }

    public function withPaginationClientPartial(bool $paginationClientPartial): self
    {
        $self = clone $this;
        $self->paginationClientPartial = $paginationClientPartial;

        return $self;
    }

    public function getPaginationViaCursor(): ?array
    {
        return $this->paginationViaCursor;
    }

    public function withPaginationViaCursor(array $paginationViaCursor): self
    {
        $self = clone $this;
        $self->paginationViaCursor = $paginationViaCursor;

        return $self;
    }

    public function getPaginationEnabled(): ?bool
    {
        return $this->paginationEnabled;
    }

    public function withPaginationEnabled(bool $paginationEnabled): self
    {
        $self = clone $this;
        $self->paginationEnabled = $paginationEnabled;

        return $self;
    }

    public function getPaginationFetchJoinCollection(): ?bool
    {
        return $this->paginationFetchJoinCollection;
    }

    public function withPaginationFetchJoinCollection(bool $paginationFetchJoinCollection): self
    {
        $self = clone $this;
        $self->paginationFetchJoinCollection = $paginationFetchJoinCollection;

        return $self;
    }

    public function getPaginationUseOutputWalkers(): ?bool
    {
        return $this->paginationUseOutputWalkers;
    }

    public function withPaginationUseOutputWalkers(bool $paginationUseOutputWalkers): self
    {
        $self = clone $this;
        $self->paginationUseOutputWalkers = $paginationUseOutputWalkers;

        return $self;
    }

    public function getPaginationItemsPerPage(): ?int
    {
        return $this->paginationItemsPerPage;
    }

    public function withPaginationItemsPerPage(int $paginationItemsPerPage): self
    {
        $self = clone $this;
        $self->paginationItemsPerPage = $paginationItemsPerPage;

        return $self;
    }

    public function getPaginationMaximumItemsPerPage(): ?int
    {
        return $this->paginationMaximumItemsPerPage;
    }

    public function withPaginationMaximumItemsPerPage(int $paginationMaximumItemsPerPage): self
    {
        $self = clone $this;
        $self->paginationMaximumItemsPerPage = $paginationMaximumItemsPerPage;

        return $self;
    }

    public function getPaginationPartial(): ?bool
    {
        return $this->paginationPartial;
    }

    public function withPaginationPartial(bool $paginationPartial): self
    {
        $self = clone $this;
        $self->paginationPartial = $paginationPartial;

        return $self;
    }

    public function getPaginationType(): ?string
    {
        return $this->paginationType;
    }

    public function withPaginationType(string $paginationType): self
    {
        $self = clone $this;
        $self->paginationType = $paginationType;

        return $self;
    }

    public function getSecurity(): ?string
    {
        return $this->security;
    }

    public function withSecurity(string $security): self
    {
        $self = clone $this;
        $self->security = $security;

        return $self;
    }

    public function getSecurityMessage(): ?string
    {
        return $this->securityMessage;
    }

    public function withSecurityMessage(string $securityMessage): self
    {
        $self = clone $this;
        $self->securityMessage = $securityMessage;

        return $self;
    }

    public function getSecurityPostDenormalize(): ?string
    {
        return $this->securityPostDenormalize;
    }

    public function withSecurityPostDenormalize(string $securityPostDenormalize): self
    {
        $self = clone $this;
        $self->securityPostDenormalize = $securityPostDenormalize;

        return $self;
    }

    public function getSecurityPostDenormalizeMessage(): ?string
    {
        return $this->securityPostDenormalizeMessage;
    }

    public function withSecurityPostDenormalizeMessage(string $securityPostDenormalizeMessage): self
    {
        $self = clone $this;
        $self->securityPostDenormalizeMessage = $securityPostDenormalizeMessage;

        return $self;
    }

    public function getSecurityPostValidation(): ?string
    {
        return $this->securityPostValidation;
    }

    public function withSecurityPostValidation(string $securityPostValidation = null): self
    {
        $self = clone $this;
        $self->securityPostValidation = $securityPostValidation;

        return $self;
    }

    public function getSecurityPostValidationMessage(): ?string
    {
        return $this->securityPostValidationMessage;
    }

    public function withSecurityPostValidationMessage(string $securityPostValidationMessage = null): self
    {
        $self = clone $this;
        $self->securityPostValidationMessage = $securityPostValidationMessage;

        return $self;
    }

    public function getExceptionToStatus(): ?array
    {
        return $this->exceptionToStatus;
    }

    public function withExceptionToStatus(array $exceptionToStatus): self
    {
        $self = clone $this;
        $self->exceptionToStatus = $exceptionToStatus;

        return $self;
    }

    public function getQueryParameterValidationEnabled(): ?bool
    {
        return $this->queryParameterValidationEnabled;
    }

    public function withQueryParameterValidationEnabled(bool $queryParameterValidationEnabled): self
    {
        $self = clone $this;
        $self->queryParameterValidationEnabled = $queryParameterValidationEnabled;

        return $self;
    }

    /**
     * @return GraphQlOperation[]
     */
    public function getGraphQlOperations(): ?array
    {
        return $this->graphQlOperations;
    }

    public function withGraphQlOperations(array $graphQlOperations): self
    {
        $self = clone $this;
        $self->graphQlOperations = $graphQlOperations;

        return $self;
    }

    /**
     * @return string|callable|null
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    public function withProcessor($processor): self
    {
        $self = clone $this;
        $self->processor = $processor;

        return $self;
    }

    /**
     * @return string|callable|null
     */
    public function getProvider()
    {
        return $this->provider;
    }

    public function withProvider($provider): self
    {
        $self = clone $this;
        $self->provider = $provider;

        return $self;
    }

    public function getExtraProperties(): array
    {
        return $this->extraProperties;
    }

    public function withExtraProperties(array $extraProperties): self
    {
        $self = clone $this;
        $self->extraProperties = $extraProperties;

        return $self;
    }

    public function getStateOptions(): ?OptionsInterface
    {
        return $this->stateOptions;
    }

    public function withStateOptions(?OptionsInterface $stateOptions): self
    {
        $self = clone $this;
        $self->stateOptions = $stateOptions;

        return $self;
    }
}
