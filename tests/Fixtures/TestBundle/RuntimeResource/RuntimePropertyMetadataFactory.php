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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Symfony\Component\TypeInfo\Type;

final class RuntimePropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(
        private readonly PropertyMetadataFactoryInterface $decorated,
        private readonly RuntimeResources $resources,
    ) {
    }

    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        if (null === $definition = $this->resources->get($resourceClass)) {
            return $this->decorated->create($resourceClass, $property, $options);
        }

        if ('id' === $property) {
            return new ApiProperty(readable: true, writable: false, identifier: true, nativeType: Type::int());
        }

        if ('resource' === $property) {
            return new ApiProperty(readable: false, writable: false, nativeType: Type::string());
        }

        $attribute = $definition->attributes[$property] ?? throw new PropertyNotFoundException(\sprintf('Property "%s" of "%s" not found.', $property, $resourceClass));
        $type = match ($attribute->type) {
            AttributeType::String, AttributeType::Text, AttributeType::Enum => Type::string(),
            AttributeType::Integer => Type::int(),
            AttributeType::Float => Type::float(),
            AttributeType::Boolean => Type::bool(),
            AttributeType::Datetime => Type::object(\DateTimeImmutable::class),
            AttributeType::Reference => Type::object((string) $this->resources->classOf((string) $attribute->target())),
        };

        return new ApiProperty(
            readable: true,
            writable: true,
            required: $attribute->required,
            readableLink: true,
            writableLink: false,
            schema: array_filter(['maxLength' => $attribute->options['maxLength'] ?? null, 'enum' => $attribute->options['values'] ?? null]) ?: null,
            nativeType: match (true) {
                $attribute->collection => Type::list($type),
                $attribute->required => $type,
                default => Type::nullable($type),
            },
        );
    }
}
