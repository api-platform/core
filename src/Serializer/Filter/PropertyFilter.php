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

namespace ApiPlatform\Core\Serializer\Filter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Property filter.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class PropertyFilter implements FilterInterface
{
    private $overrideDefaultProperties;
    private $parameterName;
    private $whitelist;

    public function __construct(string $parameterName = 'properties', bool $overrideDefaultProperties = false, array $whitelist = null)
    {
        $this->overrideDefaultProperties = $overrideDefaultProperties;
        $this->parameterName = $parameterName;
        $this->whitelist = null === $whitelist ? null : $this->formatWhitelist($whitelist);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, bool $normalization, array $attributes, array &$context)
    {
        if (null !== $propertyAttribute = $request->attributes->get('_api_filter_property')) {
            $properties = $propertyAttribute;
        } elseif (array_key_exists($this->parameterName, $commonAttribute = $request->attributes->get('_api_filters', []))) {
            $properties = $commonAttribute[$this->parameterName];
        } else {
            $properties = $request->query->get($this->parameterName);
        }

        if (!\is_array($properties)) {
            return;
        }

        if (null !== $this->whitelist) {
            $properties = $this->getProperties($properties, $this->whitelist);
        }

        if (!$this->overrideDefaultProperties && isset($context[AbstractNormalizer::ATTRIBUTES])) {
            $properties = array_merge_recursive((array) $context[AbstractNormalizer::ATTRIBUTES], $properties);
        }

        $context[AbstractNormalizer::ATTRIBUTES] = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        return [
            "$this->parameterName[]" => [
                'property' => null,
                'type' => 'string',
                'required' => false,
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

    private function getProperties(array $properties, array $whitelist = null): array
    {
        $whitelist = $whitelist ?? $this->whitelist;
        $result = [];

        foreach ($properties as $key => $value) {
            if (is_numeric($key)) {
                if (\in_array($value, $whitelist, true)) {
                    $result[] = $value;
                }

                continue;
            }

            if (\is_array($value) && isset($whitelist[$key]) && $recursiveResult = $this->getProperties($value, $whitelist[$key])) {
                $result[$key] = $recursiveResult;
            }
        }

        return $result;
    }
}
