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

    public function __construct(string $parameterName = 'properties', bool $overrideDefaultProperties = false)
    {
        $this->overrideDefaultProperties = $overrideDefaultProperties;
        $this->parameterName = $parameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, bool $normalization, array $attributes, array &$context)
    {
        if (!is_array($properties = $request->query->get($this->parameterName))) {
            return;
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
}
