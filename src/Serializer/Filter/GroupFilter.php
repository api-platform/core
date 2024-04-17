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

namespace ApiPlatform\Serializer\Filter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * The group filter allows you to filter by serialization groups.
 *
 * Syntax: `?groups[]=<group>`.
 *
 * You can add as many groups as you need.
 *
 * Three arguments are available to configure the filter:
 * - `parameterName` is the query parameter name (default: `groups`)
 * - `overrideDefaultGroups` allows to override the default serialization groups (default: `false`)
 * - `whitelist` groups whitelist to avoid uncontrolled data exposure (default: `null` to allow all groups)
 *
 * <div data-code-selector>
 *
 * ```php
 * <?php
 * // api/src/Entity/Book.php
 * use ApiPlatform\Metadata\ApiFilter;
 * use ApiPlatform\Metadata\ApiResource;
 * use ApiPlatform\Serializer\Filter\GroupFilter;
 *
 * #[ApiResource]
 * #[ApiFilter(GroupFilter::class, arguments: ['parameterName' => 'groups', 'overrideDefaultGroups' => false, 'whitelist' => ['allowed_group']])]
 * class Book
 * {
 *     // ...
 * }
 * ```
 *
 * ```yaml
 * # config/services.yaml
 * services:
 *     book.group_filter:
 *         parent: 'api_platform.serializer.group_filter'
 *         arguments: [ $parameterName: 'groups', $overrideDefaultGroups: false, $whitelist: ['allowed_group'] ]
 *         tags:  [ 'api_platform.filter' ]
 *         # The following are mandatory only if a _defaults section is defined with inverted values.
 *         # You may want to isolate filters in a dedicated file to avoid adding the following lines (by adding them in the defaults section)
 *         autowire: false
 *         autoconfigure: false
 *         public: false
 *
 * # api/config/api_platform/resources.yaml
 * resources:
 *     App\Entity\Book:
 *         - operations:
 *               ApiPlatform\Metadata\GetCollection:
 *                   filters: ['book.group_filter']
 * ```
 *
 * ```xml
 * <?xml version="1.0" encoding="UTF-8" ?>
 * <!-- api/config/services.xml -->
 * <?xml version="1.0" encoding="UTF-8" ?>
 * <container
 *         xmlns="http://symfony.com/schema/dic/services"
 *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 *         xsi:schemaLocation="http://symfony.com/schema/dic/services
 *         https://symfony.com/schema/dic/services/services-1.0.xsd">
 *     <services>
 *         <service id="book.group_filter" parent="api_platform.serializer.group_filter">
 *             <argument key="parameterName">groups</argument>
 *             <argument key="overrideDefaultGroups">false</argument>
 *             <argument key="whitelist" type="collection">
 *                 <argument>allowed_group</argument>
 *             </argument>
 *             <tag name="api_platform.filter"/>
 *         </service>
 *     </services>
 * </container>
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
 *                     <filter>book.group_filter</filter>
 *                 </filters>
 *             </operation>
 *         </operations>
 *     </resource>
 * </resources>
 * ```
 *
 * </div>
 *
 * Given that the collection endpoint is `/books`, you can filter books by serialization groups with the following query: `/books?groups[]=read&groups[]=write`.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class GroupFilter implements FilterInterface
{
    public function __construct(private readonly string $parameterName = 'groups', private readonly bool $overrideDefaultGroups = false, private readonly ?array $whitelist = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, bool $normalization, array $attributes, array &$context): void
    {
        if (\array_key_exists($this->parameterName, $commonAttribute = $request->attributes->get('_api_filters', []))) {
            $groups = $commonAttribute[$this->parameterName];
        } else {
            $groups = $request->query->all()[$this->parameterName] ?? null;
        }

        if (!\is_array($groups)) {
            return;
        }

        if (null !== $this->whitelist) {
            $groups = array_intersect($this->whitelist, $groups);
        }

        if (!$this->overrideDefaultGroups && isset($context[AbstractNormalizer::GROUPS])) {
            $groups = array_merge((array) $context[AbstractNormalizer::GROUPS], $groups);
        }

        $context[AbstractNormalizer::GROUPS] = $groups;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [
            'type' => 'string',
            'is_collection' => true,
            'required' => false,
        ];

        if ($this->whitelist) {
            $description['schema'] = [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                    'enum' => $this->whitelist,
                ],
            ];
        }

        return ["$this->parameterName[]" => $description];
    }
}
