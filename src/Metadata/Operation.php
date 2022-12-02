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

use ApiPlatform\State\OptionsInterface;

/**
 * ⚠ This class and its children offer no backward compatibility regarding positional parameters.
 */
abstract class Operation
{
    use WithResourceTrait;

    /**
     * @param bool|null                           $paginationEnabled              {@see https://api-platform.com/docs/core/pagination/#for-a-specific-resource}
     * @param string|null                         $paginationType                 {@see https://api-platform.com/docs/core/graphql/#using-the-page-based-pagination}
     * @param int|null                            $paginationItemsPerPage         {@see https://api-platform.com/docs/core/pagination/#changing-the-number-of-items-per-page}
     * @param int|null                            $paginationMaximumItemsPerPage  {@see https://api-platform.com/docs/core/pagination/#changing-maximum-items-per-page}
     * @param bool|null                           $paginationPartial              {@see https://api-platform.com/docs/core/performance/#partial-pagination}
     * @param bool|null                           $paginationClientEnabled        {@see https://api-platform.com/docs/core/pagination/#for-a-specific-resource-1}
     * @param bool|null                           $paginationClientItemsPerPage   {@see https://api-platform.com/docs/core/pagination/#for-a-specific-resource-3}
     * @param bool|null                           $paginationClientPartial        {@see https://api-platform.com/docs/core/pagination/#for-a-specific-resource-6}
     * @param bool|null                           $paginationFetchJoinCollection  {@see https://api-platform.com/docs/core/pagination/#controlling-the-behavior-of-the-doctrine-orm-paginator}
     * @param array<string, string>|string[]|null $order                          {@see https://api-platform.com/docs/core/default-order/#overriding-default-order}
     * @param string|null                         $security                       {@see https://api-platform.com/docs/core/security}
     * @param string|null                         $securityMessage                {@see https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message}
     * @param string|null                         $securityPostDenormalize        {@see https://api-platform.com/docs/core/security/#executing-access-control-rules-after-denormalization}
     * @param string|null                         $securityPostDenormalizeMessage {@see https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message}
     * @param string|null                         $deprecationReason              {@see https://api-platform.com/docs/core/deprecations/#deprecating-resource-classes-operations-and-properties}
     * @param string[]|null                       $filters                        {@see https://api-platform.com/docs/core/filters/#doctrine-orm-and-mongodb-odm-filters}
     * @param array{
     *     class?: string|null,
     *     name?: string,
     * }|string|false|null $input {@see https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation}
     * @param array{
     *     class?: string|null,
     *     name?: string,
     * }|string|false|null $output {@see https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation}
     * @param string|array|bool|null $mercure       {@see https://api-platform.com/docs/core/mercure}
     * @param string|bool|null       $messenger     {@see https://api-platform.com/docs/core/messenger/#dispatching-a-resource-through-the-message-bus}
     * @param bool|null              $elasticsearch {@see https://api-platform.com/docs/core/elasticsearch/}
     * @param bool|null              $read          {@see https://api-platform.com/docs/core/events/#the-event-system}
     * @param bool|null              $deserialize   {@see https://api-platform.com/docs/core/events/#the-event-system}
     * @param bool|null              $validate      {@see https://api-platform.com/docs/core/events/#the-event-system}
     * @param bool|null              $write         {@see https://api-platform.com/docs/core/events/#the-event-system}
     * @param bool|null              $serialize     {@see https://api-platform.com/docs/core/events/#the-event-system}
     * @param bool|null              $fetchPartial  {@see https://api-platform.com/docs/core/performance/#fetch-partial}
     * @param bool|null              $forceEager    {@see https://api-platform.com/docs/core/performance/#force-eager}
     * @param string|callable|null   $provider      {@see https://api-platform.com/docs/core/state-providers/#state-providers}
     * @param string|callable|null   $processor     {@see https://api-platform.com/docs/core/state-processors/#state-processors}
     */
    public function __construct(
        protected ?string $shortName = null,
        protected ?string $class = null,
        /**
         * The `paginationEnabled` option enables (or disables) the pagination for the current collection operation.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationEnabled: true)]
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
         *         - operations:
         *               ApiPlatform\Metadata\GetCollection:
         *                   paginationEnabled: true
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
         *     <resource class="App\Entity\Book">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationEnabled=true />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * </CodeSelector>
         */
        protected ?bool $paginationEnabled = null,
        /**
         * The `paginationType` option defines the type of pagination (`page` or `cursor`) to use for the current collection operation.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationType: 'page')]
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
         *         - operations:
         *               ApiPlatform\Metadata\GetCollection:
         *                   paginationType: page
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
         *     <resource class="App\Entity\Book">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationType="page" />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * </CodeSelector>
         */
        protected ?string $paginationType = null,
        /**
         * The `paginationItemsPerPage` option defines the number of items per page for the current collection operation.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationItemsPerPage: 30)]
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
         *         - operations:
         *               ApiPlatform\Metadata\GetCollection:
         *                   paginationItemsPerPage: 30
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
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationItemsPerPage=30 />
         *         </operations>
         *     </resource>
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
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationMaximumItemsPerPage: 50)]
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
         *         - operations:
         *               ApiPlatform\Metadata\GetCollection:
         *                   paginationMaximumItemsPerPage: 50
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
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationMaximumItemsPerPage=50 />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * </CodeSelector>
         */
        protected ?int $paginationMaximumItemsPerPage = null,
        /**
         * The `paginationPartial` option enables (or disables) the partial pagination for the current collection operation.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationPartial: true)]
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
         *         - operations:
         *               ApiPlatform\Metadata\GetCollection:
         *                   paginationPartial: true
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
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationPartial=true />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * </CodeSelector>
         */
        protected ?bool $paginationPartial = null,
        /**
         * The `paginationClientEnabled` option allows (or disallows) the client to enable (or disable) the pagination for the current collection operation.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationClientEnabled: true)]
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
         *         - operations:
         *               ApiPlatform\Metadata\GetCollection:
         *                   paginationClientEnabled: true
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
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationClientEnabled=true />
         *         </operations>
         *     </resource>
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
         * The `paginationClientItemsPerPage` option allows (or disallows) the client to set the number of items per page for the current collection operation.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationClientItemsPerPage: true)]
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
         *         - operations:
         *               ApiPlatform\Metadata\GetCollection:
         *                   paginationClientItemsPerPage: true
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
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationClientItemsPerPage=true />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * </CodeSelector>
         *
         * The number of items can now be set by adding a query parameter named `itemsPerPage`:
         * - `GET /books?itemsPerPage=50`
         */
        protected ?bool $paginationClientItemsPerPage = null,
        /**
         * The `paginationClientPartial` option allows (or disallows) the client to enable (or disable) the partial pagination for the current collection operation.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationClientPartial: true)]
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
         *         - operations:
         *               ApiPlatform\Metadata\GetCollection:
         *                   paginationClientPartial: true
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
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationClientPartial=true />
         *         </operations>
         *     </resource>
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
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationFetchJoinCollection: false)]
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
         *         - operations:
         *               ApiPlatform\Metadata\GetCollection:
         *                   paginationFetchJoinCollection: false
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
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationFetchJoinCollection=false />
         *         </operations>
         *     </resource>
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
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationUseOutputWalkers: false)]
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
         *         - operations:
         *               ApiPlatform\Metadata\GetCollection:
         *                   paginationUseOutputWalkers: false
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
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationUseOutputWalkers=false />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * </CodeSelector>
         *
         * For more information, please see the [Pagination](https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/pagination.html) entry in the Doctrine ORM documentation.
         */
        protected ?bool $paginationUseOutputWalkers = null,
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
         * use ApiPlatform\Metadata\GetCollection;
         * use ApiPlatform\Doctrine\Odm\Filter\OrderFilter;
         * use ApiPlatform\Doctrine\Odm\Filter\RangeFilter;
         *
         * #[GetCollection(paginationPartial: true, paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']])]
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
         *         - operations:
         *               ApiPlatform\Metadata\GetCollection:
         *                   paginationPartial: true
         *                   paginationViaCursor:
         *                       - { field: 'id', direction: 'DESC' }
         *                   filters: [ 'app.filters.book.range', 'app.filters.book.order' ]
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
         *     <resource class="App\Entity\Book">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationPartial=true>
         *                 <filters>
         *                     <filter>app.filters.book.range</filter>
         *                     <filter>app.filters.book.order</filter>
         *                 </filters>
         *                 <paginationViaCursor>
         *                     <paginationField field="id" direction="DESC" />
         *                 </paginationViaCursor>
         *             </operation>
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * </CodeSelector>
         *
         * To know more about cursor-based pagination take a look at [this blog post on medium (draft)](https://medium.com/@sroze/74fd1d324723).
         */
        protected ?array $paginationViaCursor = null,
        protected ?array $order = null,
        protected ?string $description = null,
        protected ?array $normalizationContext = null,
        protected ?array $denormalizationContext = null,
        protected ?bool $collectDenormalizationErrors = null,
        protected ?string $security = null,
        protected ?string $securityMessage = null,
        protected ?string $securityPostDenormalize = null,
        protected ?string $securityPostDenormalizeMessage = null,
        protected ?string $securityPostValidation = null,
        protected ?string $securityPostValidationMessage = null,
        /**
         * The `deprecationReason` option deprecates the current operation with a deprecation message.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Parchment.php
         * use ApiPlatform\Metadata\Get;
         *
         * #[Get(deprecationReason: 'Create a Book instead')]
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
         *         - operations:
         *               ApiPlatform\Metadata\Get:
         *                   deprecationReason: 'Create a Book instead'
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
         *     <resource class="App\Entity\Parchment">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\Get" deprecationReason="Create a Book instead" />
         *         <operations>
         *     </resource>
         * </resources>
         * ```
         * </CodeSelector>
         *
         * - With JSON-lD / Hydra, [an `owl:deprecated` annotation property](https://www.w3.org/TR/owl2-syntax/#Annotation_Properties) will be added to the appropriate data structure
         * - With Swagger / OpenAPI, [a `deprecated` property](https://swagger.io/docs/specification/2-0/paths-and-operations/) will be added
         * - With GraphQL, the [`isDeprecated` and `deprecationReason` properties](https://facebook.github.io/graphql/June2018/#sec-Deprecation) will be added to the schema
         */
        protected ?string $deprecationReason = null,
        /**
         * The `filters` option configures the filters (declared as services) available on the collection routes for the current resource.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(filters: ['app.filters.book.search'])]
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
         *         - operations:
         *               ApiPlatform\Metadata\GetCollection:
         *                   filters: ['app.filters.book.search']
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
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection">
         *                 <filters>
         *                     <filter>app.filters.book.search</filter>
         *                 </filters>
         *             </operation>
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * </CodeSelector>
         */
        protected ?array $filters = null,
        /**
         * The `validationContext` option configure the context of validation for the current Operation.
         * You can, for instance, describe the validation groups that will be used :.
         *
         * ```php
         *   #[Put(validationContext: ['groups' => ['Default', 'putValidation']])]
         *   #[Post(validationContext: ['groups' => ['Default', 'postValidation']])]
         * ```
         *
         * For more examples, read our guide on [validation](/guides/validation).
         */
        protected ?array $validationContext = null,
        protected $input = null,
        protected $output = null,
        protected $mercure = null,
        /**
         * The `messenger` option dispatches the current resource through the Message Bus.
         *
         * <CodeSelector>
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\Post;
         *
         * #[Post(messenger: true)]
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
         *         - operations:
         *               ApiPlatform\Metadata\Post:
         *                   messenger: true
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
         *     <resource class="App\Entity\Book">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\Post" messenger=true />
         *         </operations>
         *     </resource>
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
        protected ?bool $elasticsearch = null,
        protected ?int $urlGenerationStrategy = null,
        protected ?bool $read = null,
        protected ?bool $deserialize = null,
        protected ?bool $validate = null,
        protected ?bool $write = null,
        protected ?bool $serialize = null,
        protected ?bool $fetchPartial = null,
        protected ?bool $forceEager = null,
        protected ?int $priority = null,
        protected ?string $name = null,
        protected $provider = null,
        protected $processor = null,
        protected ?OptionsInterface $stateOptions = null,
        protected array $extraProperties = [],
    ) {
    }

    public function withOperation($operation)
    {
        return $this->copyFrom($operation);
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function withShortName(string $shortName = null): self
    {
        $self = clone $this;
        $self->shortName = $shortName;

        return $self;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function withClass(string $class = null): self
    {
        $self = clone $this;
        $self->class = $class;

        return $self;
    }

    public function getPaginationEnabled(): ?bool
    {
        return $this->paginationEnabled;
    }

    public function withPaginationEnabled(bool $paginationEnabled = null): self
    {
        $self = clone $this;
        $self->paginationEnabled = $paginationEnabled;

        return $self;
    }

    public function getPaginationType(): ?string
    {
        return $this->paginationType;
    }

    public function withPaginationType(string $paginationType = null): self
    {
        $self = clone $this;
        $self->paginationType = $paginationType;

        return $self;
    }

    public function getPaginationItemsPerPage(): ?int
    {
        return $this->paginationItemsPerPage;
    }

    public function withPaginationItemsPerPage(int $paginationItemsPerPage = null): self
    {
        $self = clone $this;
        $self->paginationItemsPerPage = $paginationItemsPerPage;

        return $self;
    }

    public function getPaginationMaximumItemsPerPage(): ?int
    {
        return $this->paginationMaximumItemsPerPage;
    }

    public function withPaginationMaximumItemsPerPage(int $paginationMaximumItemsPerPage = null): self
    {
        $self = clone $this;
        $self->paginationMaximumItemsPerPage = $paginationMaximumItemsPerPage;

        return $self;
    }

    public function getPaginationPartial(): ?bool
    {
        return $this->paginationPartial;
    }

    public function withPaginationPartial(bool $paginationPartial = null): self
    {
        $self = clone $this;
        $self->paginationPartial = $paginationPartial;

        return $self;
    }

    public function getPaginationClientEnabled(): ?bool
    {
        return $this->paginationClientEnabled;
    }

    public function withPaginationClientEnabled(bool $paginationClientEnabled = null): self
    {
        $self = clone $this;
        $self->paginationClientEnabled = $paginationClientEnabled;

        return $self;
    }

    public function getPaginationClientItemsPerPage(): ?bool
    {
        return $this->paginationClientItemsPerPage;
    }

    public function withPaginationClientItemsPerPage(bool $paginationClientItemsPerPage = null): self
    {
        $self = clone $this;
        $self->paginationClientItemsPerPage = $paginationClientItemsPerPage;

        return $self;
    }

    public function getPaginationClientPartial(): ?bool
    {
        return $this->paginationClientPartial;
    }

    public function withPaginationClientPartial(bool $paginationClientPartial = null): self
    {
        $self = clone $this;
        $self->paginationClientPartial = $paginationClientPartial;

        return $self;
    }

    public function getPaginationFetchJoinCollection(): ?bool
    {
        return $this->paginationFetchJoinCollection;
    }

    public function withPaginationFetchJoinCollection(bool $paginationFetchJoinCollection = null): self
    {
        $self = clone $this;
        $self->paginationFetchJoinCollection = $paginationFetchJoinCollection;

        return $self;
    }

    public function getPaginationUseOutputWalkers(): ?bool
    {
        return $this->paginationUseOutputWalkers;
    }

    public function withPaginationUseOutputWalkers(bool $paginationUseOutputWalkers = null): self
    {
        $self = clone $this;
        $self->paginationUseOutputWalkers = $paginationUseOutputWalkers;

        return $self;
    }

    public function getOrder(): ?array
    {
        return $this->order;
    }

    public function withOrder(array $order = []): self
    {
        $self = clone $this;
        $self->order = $order;

        return $self;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function withDescription(string $description = null): self
    {
        $self = clone $this;
        $self->description = $description;

        return $self;
    }

    public function getNormalizationContext(): ?array
    {
        return $this->normalizationContext;
    }

    public function withNormalizationContext(array $normalizationContext = []): self
    {
        $self = clone $this;
        $self->normalizationContext = $normalizationContext;

        return $self;
    }

    public function getDenormalizationContext(): ?array
    {
        return $this->denormalizationContext;
    }

    public function withDenormalizationContext(array $denormalizationContext = []): self
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

    public function getSecurity(): ?string
    {
        return $this->security;
    }

    public function withSecurity(string $security = null): self
    {
        $self = clone $this;
        $self->security = $security;

        return $self;
    }

    public function getSecurityMessage(): ?string
    {
        return $this->securityMessage;
    }

    public function withSecurityMessage(string $securityMessage = null): self
    {
        $self = clone $this;
        $self->securityMessage = $securityMessage;

        return $self;
    }

    public function getSecurityPostDenormalize(): ?string
    {
        return $this->securityPostDenormalize;
    }

    public function withSecurityPostDenormalize(string $securityPostDenormalize = null): self
    {
        $self = clone $this;
        $self->securityPostDenormalize = $securityPostDenormalize;

        return $self;
    }

    public function getSecurityPostDenormalizeMessage(): ?string
    {
        return $this->securityPostDenormalizeMessage;
    }

    public function withSecurityPostDenormalizeMessage(string $securityPostDenormalizeMessage = null): self
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

    public function getDeprecationReason(): ?string
    {
        return $this->deprecationReason;
    }

    public function withDeprecationReason(string $deprecationReason = null): self
    {
        $self = clone $this;
        $self->deprecationReason = $deprecationReason;

        return $self;
    }

    public function getFilters(): ?array
    {
        return $this->filters;
    }

    public function withFilters(array $filters = []): self
    {
        $self = clone $this;
        $self->filters = $filters;

        return $self;
    }

    public function getValidationContext(): ?array
    {
        return $this->validationContext;
    }

    public function withValidationContext(array $validationContext = []): self
    {
        $self = clone $this;
        $self->validationContext = $validationContext;

        return $self;
    }

    public function getInput()
    {
        return $this->input;
    }

    public function withInput($input = null): self
    {
        $self = clone $this;
        $self->input = $input;

        return $self;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function withOutput($output = null): self
    {
        $self = clone $this;
        $self->output = $output;

        return $self;
    }

    public function getMercure()
    {
        return $this->mercure;
    }

    public function withMercure($mercure = null): self
    {
        $self = clone $this;
        $self->mercure = $mercure;

        return $self;
    }

    public function getMessenger()
    {
        return $this->messenger;
    }

    public function withMessenger($messenger = null): self
    {
        $self = clone $this;
        $self->messenger = $messenger;

        return $self;
    }

    public function getElasticsearch(): ?bool
    {
        return $this->elasticsearch;
    }

    public function withElasticsearch(bool $elasticsearch = null): self
    {
        $self = clone $this;
        $self->elasticsearch = $elasticsearch;

        return $self;
    }

    public function getUrlGenerationStrategy(): ?int
    {
        return $this->urlGenerationStrategy;
    }

    public function withUrlGenerationStrategy(int $urlGenerationStrategy = null): self
    {
        $self = clone $this;
        $self->urlGenerationStrategy = $urlGenerationStrategy;

        return $self;
    }

    public function canRead(): ?bool
    {
        return $this->read;
    }

    public function withRead(bool $read = true): self
    {
        $self = clone $this;
        $self->read = $read;

        return $self;
    }

    public function canDeserialize(): ?bool
    {
        return $this->deserialize;
    }

    public function withDeserialize(bool $deserialize = true): self
    {
        $self = clone $this;
        $self->deserialize = $deserialize;

        return $self;
    }

    public function canValidate(): ?bool
    {
        return $this->validate;
    }

    public function withValidate(bool $validate = true): self
    {
        $self = clone $this;
        $self->validate = $validate;

        return $self;
    }

    public function canWrite(): ?bool
    {
        return $this->write;
    }

    public function withWrite(bool $write = true): self
    {
        $self = clone $this;
        $self->write = $write;

        return $self;
    }

    public function canSerialize(): ?bool
    {
        return $this->serialize;
    }

    public function withSerialize(bool $serialize = true): self
    {
        $self = clone $this;
        $self->serialize = $serialize;

        return $self;
    }

    public function getFetchPartial(): ?bool
    {
        return $this->fetchPartial;
    }

    public function withFetchPartial(bool $fetchPartial = null): self
    {
        $self = clone $this;
        $self->fetchPartial = $fetchPartial;

        return $self;
    }

    public function getForceEager(): ?bool
    {
        return $this->forceEager;
    }

    public function withForceEager(bool $forceEager = null): self
    {
        $self = clone $this;
        $self->forceEager = $forceEager;

        return $self;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function withPriority(int $priority = 0): self
    {
        $self = clone $this;
        $self->priority = $priority;

        return $self;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function withName(string $name = ''): self
    {
        $self = clone $this;
        $self->name = $name;

        return $self;
    }

    public function getProcessor(): callable|string|null
    {
        return $this->processor;
    }

    public function withProcessor(callable|string|null $processor): self
    {
        $self = clone $this;
        $self->processor = $processor;

        return $self;
    }

    public function getProvider(): callable|string|null
    {
        return $this->provider;
    }

    public function withProvider(callable|string|null $provider): self
    {
        $self = clone $this;
        $self->provider = $provider;

        return $self;
    }

    public function getExtraProperties(): array
    {
        return $this->extraProperties;
    }

    public function withExtraProperties(array $extraProperties = []): self
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
