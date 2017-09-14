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
        if (!is_array($properties = $request->query->get($this->parameterName))) {
            return;
        }

        if (null !== $this->whitelist) {
            $properties = $this->intersectArrayRecursive($properties, $this->whitelist);
        }

        if (!$this->overrideDefaultProperties && isset($context['attributes'])) {
            $properties = array_merge_recursive((array) $context['attributes'], $properties);
        }

        $context['attributes'] = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        return [
            $this->parameterName.'[]' => [
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
     * Will transform ['foo', 'group.bar.'baz', 'fuz']
     * into ['foo', 'group' => ['bar' => 'baz'], 'fuz']
     *
     * @param array $whitelist the whitelist to format
     *
     * @return array an array containing the whitelist ready to match request parameters
     */
    private function formatWhitelist(array $whitelist): array
    {
        $results = [];
        foreach ($whitelist as $propertyPath) {
            $segments = explode('.', $propertyPath);
            $root = array_shift($segments);
            if ($segments) {
                $segments = array_reverse($segments);
                $child = [$segments[0]];
                array_shift($segments);
                foreach ($segments as $segment) {
                    $child = [$segment => $child];
                }
                $results[$root] = array_merge_recursive($results[$root] ?? [], $child);
            } else {
                $results[] = $root;
            }
        }

        return $results;
    }

    /**
     * Computes the intersection of arrays recursively for arrays with a mix of numeric and associatives keys.
     *
     * @param array $array1 the array with master values to check
     * @param array $array2 An array to compare values against
     *
     * @return array an array containing all of the values in array1 recursively whose values exist in array2
     */
    private function intersectArrayRecursive(array $array1, array $array2): array
    {
        list($numeric1, $assoc1) = $this->partitionAssocAndNumericArrayKeys($array1);
        list($numeric2, $assoc2) = $this->partitionAssocAndNumericArrayKeys($array2);

        $results = array_values(array_intersect($numeric1, $numeric2));
        foreach ($assoc1 as $key => $value) {
            if (!array_key_exists($key, $assoc2)) {
                continue;
            }
            if ($value === $assoc2[$key]) {
                $results[$key] = $value;
            } elseif (is_array($value) && is_array($assoc2[$key])) {
                $results[$key] = $this->intersectArrayRecursive($value, $assoc2[$key]);
            }
        }

        return $results;
    }

    /**
     * Partition an array in two array, the first one containing numeric keys
     * and the second one for the associative keys.
     *
     * @param array $array the array to partition
     *
     * @return array an array of two arrays
     */
    private function partitionAssocAndNumericArrayKeys(array $array): array
    {
        $numeric = $assoc = [];
        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                $numeric[] = $value;
            } else {
                $assoc[$key] = $value;
            }
        }

        return [$numeric, $assoc];
    }
}
