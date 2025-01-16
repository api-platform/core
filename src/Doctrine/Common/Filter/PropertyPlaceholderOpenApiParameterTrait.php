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

namespace ApiPlatform\Doctrine\Common\Filter;

use ApiPlatform\Metadata\Parameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;

trait PropertyPlaceholderOpenApiParameterTrait
{
    /**
     * @return array<OpenApiParameter>|null
     */
    public function getOpenApiParameters(Parameter $parameter): ?array
    {
        if (str_contains($parameter->getKey(), ':property')) {
            $parameters = [];
            $key = str_replace('[:property]', '', $parameter->getKey());
            foreach (array_keys($parameter->getExtraProperties()['_properties'] ?? []) as $property) {
                $parameters[] = new OpenApiParameter(name: \sprintf('%s[%s]', $key, $property), in: 'query');
            }

            return $parameters;
        }

        return null;
    }
}
