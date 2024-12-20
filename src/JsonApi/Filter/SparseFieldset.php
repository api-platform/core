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

use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Parameter as MetadataParameter;
use ApiPlatform\Metadata\ParameterProviderFilterInterface;
use ApiPlatform\Metadata\PropertiesAwareInterface;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter;

final class SparseFieldset implements OpenApiParameterFilterInterface, JsonSchemaFilterInterface, ParameterProviderFilterInterface, PropertiesAwareInterface
{
    public function getSchema(MetadataParameter $parameter): array
    {
        return [
            'type' => 'array',
            'items' => [
                'type' => 'string',
            ],
        ];
    }

    public function getOpenApiParameters(MetadataParameter $parameter): Parameter|array|null
    {
        return new Parameter(
            name: ($k = $parameter->getKey()).'[]',
            in: $parameter instanceof QueryParameter ? 'query' : 'header',
            description: 'Allows you to reduce the response to contain only the properties you need. If your desired property is nested, you can address it using nested arrays. Example: '.\sprintf(
                '%1$s[]={propertyName}&%1$s[]={anotherPropertyName}',
                $k
            )
        );
    }

    public static function getParameterProvider(): string
    {
        return SparseFieldsetParameterProvider::class;
    }
}
