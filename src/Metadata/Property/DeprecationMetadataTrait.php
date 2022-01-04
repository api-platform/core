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

namespace ApiPlatform\Metadata\Property;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Metadata\ApiProperty as ApiPropertyMetadata;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * @internal
 */
trait DeprecationMetadataTrait
{
    private $camelCaseToSnakeCaseNameConverter;

    private function withDeprecatedAttributes(ApiPropertyMetadata $propertyMetadata, array $attributes): ApiPropertyMetadata
    {
        $extraProperties = [];
        if (!$this->camelCaseToSnakeCaseNameConverter) {
            $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
        }

        foreach ($attributes as $key => $value) {
            $propertyName = $this->camelCaseToSnakeCaseNameConverter->denormalize($key);

            if (method_exists($propertyMetadata, $methodName = 'with'.ucfirst($propertyName))) {
                trigger_deprecation('api-platform', '2.7', sprintf('Using "%s" inside attributes on the "%s" annotation is deprecated, use "%s" on the attribute "%s" instead', $key, ApiProperty::class, $propertyName, ApiPropertyMetadata::class));
                $propertyMetadata = $propertyMetadata->{$methodName}($value);
                continue;
            }

            $extraProperties[$key] = $value;
        }

        return $propertyMetadata->withExtraProperties($propertyMetadata->getExtraProperties() + $extraProperties);
    }
}
