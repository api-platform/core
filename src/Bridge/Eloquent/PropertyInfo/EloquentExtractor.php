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

namespace ApiPlatform\Core\Bridge\Eloquent\PropertyInfo;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Extracts property type data for Eloquent models.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class EloquentExtractor implements PropertyTypeExtractorInterface, PropertyAccessExtractorInterface
{
    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes($class, $property, array $context = []): ?array
    {
        if (!is_subclass_of($class, Model::class, true)) {
            return null;
        }

        try {
            $resourceMetadata = $this->resourceMetadataFactory->create($class);
        } catch (ResourceClassNotFoundException $e) {
            return null;
        }

        if (null === $properties = $resourceMetadata->getAttribute('properties')) {
            return null;
        }

        foreach ($properties as $propertyName => $value) {
            if (!\is_array($value)) {
                continue;
            }
            if ($property === $propertyName) {
                if (isset($value['relation'])) {
                    return [
                        new Type(
                            Type::BUILTIN_TYPE_OBJECT,
                            false,
                            $value['relation']
                        ),
                    ];
                }

                if (isset($value['relationMany'])) {
                    return [
                        new Type(
                            Type::BUILTIN_TYPE_OBJECT,
                            false,
                            Collection::class,
                            true,
                            new Type(Type::BUILTIN_TYPE_INT),
                            new Type(Type::BUILTIN_TYPE_OBJECT, false, $value['relationMany'])
                        ),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable($class, $property, array $context = []): ?bool
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($class, $property, array $context = []): ?bool
    {
        if (!is_subclass_of($class, Model::class, true)) {
            return null;
        }

        /** @var Model $model */
        $model = new $class();

        if ($model->getKeyName() === $property) {
            return null;
        }

        return true;
    }
}
