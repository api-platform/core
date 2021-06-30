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

namespace ApiPlatform\Translation;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\ClassInfoTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ResourceTranslator implements ResourceTranslatorInterface
{
    use ClassInfoTrait;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory)
    {
    }

    public function isResourceTranslatable(object $resource): bool
    {
        return $this->isResourceClassTranslatable($this->getObjectClass($resource));
    }

    public function isResourceClassTranslatable(string $resourceClass): bool
    {
        $reflectionClass = new \ReflectionClass($this->getRealClassName($resourceClass));

        return $reflectionClass->implementsInterface(TranslatableInterface::class);
    }

    public function isAllTranslationsEnabled(string $resourceClass, array $clientParameters): bool
    {
        $enabled = false;

        try {
            $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
            $operation = $resourceMetadataCollection->getOperation();
        } catch (ResourceClassNotFoundException|OperationNotFoundException) {
            return false;
        }

        $translationMetadata = $operation->getTranslation();
        if ($translationMetadata['all_translations_enabled'] ?? false) {
            $enabled = true;
        }
        if ($translationMetadata['all_translations_client_enabled'] ?? false) {
            $enabled = filter_var($clientParameters[$translationMetadata['all_translations_client_parameter_name'] ?? 'allTranslations'] ?? $enabled, \FILTER_VALIDATE_BOOLEAN);
        }

        return $enabled;
    }

    public function getTranslationClass(string $resourceClass): string
    {
        $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
        $operation = $resourceMetadataCollection->getOperation();
        $translationMetadata = $operation->getTranslation();
        if (!isset($translationMetadata['class'])) {
            throw new \LogicException(sprintf('The "class" attribute must be defined in the "translation" configuration of the resource "%s".', $resourceClass));
        }

        return $translationMetadata['class'];
    }

    public function getLocale(): ?string
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            return null;
        }

        return $request->getLocale();
    }

    public function translateAttributeValue(object $resource, string $attribute, array $context): array|string|null
    {
        if (!$this->isResourceTranslatable($resource)) {
            return null;
        }

        if ($context['all_translations_enabled'] ?? false) {
            /** @var TranslatableInterface $resource */
            $resourceTranslations = $resource->getResourceTranslations();
            $attributeValue = [];
            foreach ($resourceTranslations as $resourceTranslation) {
                if ($resourceTranslationAttributeValue = $this->getResourceTranslationAttributeValue($resourceTranslation, $attribute)) {
                    $attributeValue[$resourceTranslation->getLocale()] = $resourceTranslationAttributeValue;
                }
            }

            return $attributeValue;
        }

        if (!$locale = $this->getLocale()) {
            return null;
        }
        /** @var TranslatableInterface $resource */
        if (!$resourceTranslation = $resource->getResourceTranslation($locale)) {
            return null;
        }

        return $this->getResourceTranslationAttributeValue($resourceTranslation, $attribute);
    }

    private function getResourceTranslationAttributeValue(TranslationInterface $resourceTranslation, string $attribute): ?string
    {
        try {
            $attributeValue = $this->propertyAccessor->getValue($resourceTranslation, $attribute);
        } catch (NoSuchPropertyException) {
            return null;
        }

        if (null !== $attributeValue && !\is_string($attributeValue)) {
            throw new \RuntimeException(sprintf('Attribute "%s" needs to be a string but is of type "%s".', $attribute, \gettype($attributeValue)));
        }

        return $attributeValue;
    }
}
