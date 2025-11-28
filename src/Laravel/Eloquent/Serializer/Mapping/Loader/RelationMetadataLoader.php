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

namespace ApiPlatform\Laravel\Eloquent\Serializer\Mapping\Loader;

use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Attribute\SerializedPath;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;

final class RelationMetadataLoader implements LoaderInterface
{
    public function __construct(private readonly ModelMetadata $modelMetadata)
    {
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        if (Model::class === $classMetadata->getName()) {
            return false;
        }

        if (!is_a($classMetadata->getName(), Model::class, true)) {
            return false;
        }

        $refl = $classMetadata->getReflectionClass();
        /** @var Model */
        $model = $refl->newInstanceWithoutConstructor();
        $attributesMetadata = $classMetadata->getAttributesMetadata();

        foreach ($this->modelMetadata->getRelations($model) as $relation) {
            $methodName = $relation['method_name'];
            if (!$refl->hasMethod($methodName)) {
                continue;
            }

            $reflMethod = $refl->getMethod($methodName);
            $propertyName = $relation['name'];

            if (!isset($attributesMetadata[$propertyName])) {
                $attributesMetadata[$propertyName] = new \Symfony\Component\Serializer\Mapping\AttributeMetadata($propertyName);
                $classMetadata->addAttributeMetadata($attributesMetadata[$propertyName]);
            }

            $attributeMetadata = $attributesMetadata[$propertyName];

            foreach ($reflMethod->getAttributes() as $a) {
                $attribute = $a->newInstance();

                match (true) {
                    $attribute instanceof Groups => array_map([$attributeMetadata, 'addGroup'], $attribute->groups),
                    $attribute instanceof MaxDepth => $attributeMetadata->setMaxDepth($attribute->maxDepth),
                    $attribute instanceof SerializedName => $attributeMetadata->setSerializedName($attribute->serializedName),
                    $attribute instanceof SerializedPath => $attributeMetadata->setSerializedPath($attribute->serializedPath),
                    $attribute instanceof Ignore => $attributeMetadata->setIgnore(true),
                    $attribute instanceof Context => $this->setAttributeContextsForGroups($attribute, $attributeMetadata),
                    default => null,
                };
            }
        }

        return true;
    }

    private function setAttributeContextsForGroups(Context $annotation, AttributeMetadataInterface $attributeMetadata): void
    {
        $context = $annotation->context;
        $groups = $annotation->groups;
        $normalizationContext = $annotation->normalizationContext;
        $denormalizationContext = $annotation->denormalizationContext;

        if ($normalizationContext || $context) {
            $attributeMetadata->setNormalizationContextForGroups($normalizationContext ?: $context, $groups);
        }

        if ($denormalizationContext || $context) {
            $attributeMetadata->setDenormalizationContextForGroups($denormalizationContext ?: $context, $groups);
        }
    }
}
