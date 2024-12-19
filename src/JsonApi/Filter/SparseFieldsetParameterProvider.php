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

namespace ApiPlatform\JsonApi\Filter;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterProviderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final readonly class SparseFieldsetParameterProvider implements ParameterProviderInterface
{
    public function provide(Parameter $parameter, array $parameters = [], array $context = []): ?Operation
    {
        if (!($operation = $context['operation'] ?? null)) {
            return null;
        }

        $allowedProperties = $parameter->getExtraProperties()['_properties'] ?? [];
        $value = $parameter->getValue();
        $normalizationContext = $operation->getNormalizationContext();

        if (!\is_array($value)) {
            return null;
        }

        $properties = [];
        $shortName = strtolower($operation->getShortName());
        foreach ($value as $resource => $fields) {
            if (strtolower($resource) === $shortName) {
                $p = &$properties;
            } else {
                $properties[$resource] = [];
                $p = &$properties[$resource];
            }

            foreach (explode(',', $fields) as $f) {
                if (\array_key_exists($f, $allowedProperties)) {
                    $p[] = $f;
                }
            }
        }

        if (isset($normalizationContext[AbstractNormalizer::ATTRIBUTES])) {
            $properties = array_merge_recursive((array) $normalizationContext[AbstractNormalizer::ATTRIBUTES], $properties);
        }

        $normalizationContext[AbstractNormalizer::ATTRIBUTES] = $properties;

        return $operation->withNormalizationContext($normalizationContext);
    }
}
