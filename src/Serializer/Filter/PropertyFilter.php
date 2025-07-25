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

use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Parameter as MetadataParameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * The property filter adds the possibility to select the properties to serialize (sparse fieldsets).
 *
 * Note: We strongly recommend using [Vulcain](https://vulcain.rocks/) instead of this filter. Vulcain is faster, allows a better hit rate, and is supported out of the box in the API Platform distribution.
 *
 * Syntax: `?properties[]=<property>&properties[<relation>][]=<property>`.
 *
 * You can add as many properties as you need.
 *
 * Three arguments are available to configure the filter:
 * - `parameterName` is the query parameter name (default: `properties`)
 * - `overrideDefaultProperties` allows to override the default serialization properties (default: `false`)
 * - `whitelist` properties whitelist to avoid uncontrolled data exposure (default: `null` to allow all properties)
 *
 * <div data-code-selector>
 *
 * ```php
 * <?php
 * // api/src/Entity/Book.php
 * use ApiPlatform\Metadata\ApiFilter;
 * use ApiPlatform\Metadata\ApiResource;
 * use ApiPlatform\Serializer\Filter\PropertyFilter;
 *
 * #[ApiResource]
 * #[ApiFilter(PropertyFilter::class, arguments: ['parameterName' => 'properties', 'overrideDefaultProperties' => false, 'whitelist' => ['allowed_property']])]
 * class Book
 * {
 *     // ...
 * }
 * ```
 *
 * ```yaml
 * # config/services.yaml
 * services:
 *     book.property_filter:
 *         parent: 'api_platform.serializer.property_filter'
 *         arguments: [ $parameterName: 'properties', $overrideDefaultGroups: false, $whitelist: ['allowed_property'] ]
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
 *                   filters: ['book.property_filter']
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
 *         <service id="book.property_filter" parent="api_platform.serializer.property_filter">
 *             <argument key="parameterName">properties</argument>
 *             <argument key="overrideDefaultGroups">false</argument>
 *             <argument key="whitelist" type="collection">
 *                 <argument>allowed_property</argument>
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
 *                     <filter>book.property_filter</filter>
 *                 </filters>
 *             </operation>
 *         </operations>
 *     </resource>
 * </resources>
 * ```
 *
 * </div>
 *
 * Given that the collection endpoint is `/books`, you can filter the serialization properties with the following query: `/books?properties[]=title&properties[]=author`. If you want to include some properties of the nested "author" document, use: `/books?properties[]=title&properties[author][]=name`.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class PropertyFilter implements FilterInterface, OpenApiParameterFilterInterface, JsonSchemaFilterInterface
{
    private ?array $whitelist;

    public function __construct(private readonly string $parameterName = 'properties', private readonly bool $overrideDefaultProperties = false, ?array $whitelist = null, private readonly ?NameConverterInterface $nameConverter = null)
    {
        $this->whitelist = null === $whitelist ? null : $this->formatWhitelist($whitelist);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, bool $normalization, array $attributes, array &$context): void
    {
        // TODO: ideally we should return the new context, not mutate the context given in our arguments which is the serializer context
        // this would allow to use `Parameter::filterContext` properly, for now let's retrieve it like this:
        /** @var MetadataParameter|null */
        $parameter = $request->attributes->get('_api_parameter', null);
        $parameterName = $this->parameterName;
        $whitelist = $this->whitelist;
        $overrideDefaultProperties = $this->overrideDefaultProperties;

        if ($parameter) {
            $parameterName = $parameter->getKey();
            $whitelist = $parameter->getFilterContext()['whitelist'] ?? $this->whitelist;
            $overrideDefaultProperties = $parameter->getFilterContext()['override_default_properties'] ?? $this->overrideDefaultProperties;
        }

        if (null !== $propertyAttribute = $request->attributes->get('_api_filter_property')) {
            $properties = $propertyAttribute;
        } elseif (\array_key_exists($parameterName, $commonAttribute = $request->attributes->get('_api_filters', []))) {
            $properties = $commonAttribute[$parameterName];
        } else {
            $properties = $request->query->all()[$parameterName] ?? null;
        }

        if (!\is_array($properties)) {
            return;
        }

        // TODO: when refactoring this eventually, note that the ParameterResourceMetadataCollectionFactory already does that and caches this behavior in our Parameter metadata
        $properties = $this->denormalizeProperties($properties);

        if (null !== $whitelist) {
            $properties = $this->getProperties($properties, $whitelist);
        }

        if (!$overrideDefaultProperties && isset($context[AbstractNormalizer::ATTRIBUTES])) {
            $properties = array_merge_recursive((array) $context[AbstractNormalizer::ATTRIBUTES], $properties);
        }

        $context[AbstractNormalizer::ATTRIBUTES] = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $example = \sprintf(
            '%1$s[]={propertyName}&%1$s[]={anotherPropertyName}&%1$s[{nestedPropertyParent}][]={nestedProperty}',
            $this->parameterName
        );

        return [
            "$this->parameterName[]" => [
                'type' => 'string',
                'is_collection' => true,
                'required' => false,
                'description' => 'Allows you to reduce the response to contain only the properties you need. If your desired property is nested, you can address it using nested arrays. Example: '.$example,
                'openapi' => new Parameter(
                    in: 'query',
                    name: "$this->parameterName[]",
                    description: 'Allows you to reduce the response to contain only the properties you need. If your desired property is nested, you can address it using nested arrays. Example: '.$example,
                    schema: [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ]
                ),
            ],
        ];
    }

    /**
     * Generate an array of whitelist properties to match the format that properties
     * will have in the request.
     *
     * @param array $whitelist the whitelist to format
     *
     * @return array An array containing the whitelist ready to match request parameters
     */
    private function formatWhitelist(array $whitelist): array
    {
        if (array_values($whitelist) === $whitelist) {
            return $whitelist;
        }
        foreach ($whitelist as $name => $value) {
            if (null === $value) {
                unset($whitelist[$name]);
                $whitelist[] = $name;
            }
        }

        return $whitelist;
    }

    private function getProperties(array $properties, ?array $whitelist = null): array
    {
        $whitelist ??= $this->whitelist;
        $result = [];

        foreach ($properties as $key => $value) {
            if (is_numeric($key)) {
                if (\in_array($propertyName = $this->denormalizePropertyName($value), $whitelist, true)) {
                    $result[] = $propertyName;
                }

                continue;
            }

            if (\is_array($value) && isset($whitelist[$key]) && $recursiveResult = $this->getProperties($value, $whitelist[$key])) {
                $result[$this->denormalizePropertyName($key)] = $recursiveResult;
            }
        }

        return $result;
    }

    private function denormalizeProperties(array $properties): array
    {
        if (null === $this->nameConverter || !$properties) {
            return $properties;
        }

        $result = [];
        foreach ($properties as $key => $value) {
            $result[$this->denormalizePropertyName((string) $key)] = \is_array($value) ? $this->denormalizeProperties($value) : $this->denormalizePropertyName($value);
        }

        return $result;
    }

    private function denormalizePropertyName(string $property): string
    {
        return null !== $this->nameConverter ? $this->nameConverter->denormalize($property) : $property;
    }

    public function getSchema(MetadataParameter $parameter): array
    {
        return [
            'type' => 'array',
            'items' => [
                'type' => 'string',
            ],
        ];
    }

    public function getOpenApiParameters(MetadataParameter $parameter): Parameter
    {
        $example = \sprintf(
            '%1$s[]={propertyName}&%1$s[]={anotherPropertyName}',
            $parameter->getKey()
        );

        return new Parameter(
            name: $parameter->getKey().'[]',
            in: $parameter instanceof QueryParameter ? 'query' : 'header',
            description: 'Allows you to reduce the response to contain only the properties you need. If your desired property is nested, you can address it using nested arrays. Example: '.$example
        );
    }
}
