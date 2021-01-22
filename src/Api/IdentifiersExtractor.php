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

namespace ApiPlatform\Core\Api;

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * {@inheritdoc}
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class IdentifiersExtractor implements IdentifiersExtractorInterface
{
    use ResourceClassInfoTrait;

    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $propertyAccessor;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, PropertyAccessorInterface $propertyAccessor = null, ResourceClassResolverInterface $resourceClassResolver = null)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
        $this->resourceClassResolver = $resourceClassResolver;

        if (null === $this->resourceClassResolver) {
            @trigger_error(sprintf('Not injecting %s in the IdentifiersExtractor might introduce cache issues with object identifiers.', ResourceClassResolverInterface::class), \E_USER_DEPRECATED);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifiersFromResourceClass(string $resourceClass): array
    {
        $identifiers = [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            if ($this->propertyMetadataFactory->create($resourceClass, $property)->isIdentifier() ?? false) {
                $identifiers[] = $property;
            }
        }

        return $identifiers;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifiersFromItem($item): array
    {
        $identifiers = [];
        $resourceClass = $this->getResourceClass($item, true);

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);
            $identifier = $propertyMetadata->isIdentifier();
            if (null === $identifier || false === $identifier) {
                continue;
            }

            $identifier = $identifiers[$propertyName] = $this->propertyAccessor->getValue($item, $propertyName);

            if (!\is_object($identifier)) {
                continue;
            }

            if (null === $relatedResourceClass = $this->getResourceClass($identifier)) {
                continue;
            }

            $relatedItem = $identifier;
            unset($identifiers[$propertyName]);
            foreach ($this->propertyNameCollectionFactory->create($relatedResourceClass) as $relatedPropertyName) {
                $propertyMetadata = $this->propertyMetadataFactory->create($relatedResourceClass, $relatedPropertyName);
                if ($propertyMetadata->isIdentifier()) {
                    if (isset($identifiers[$propertyName])) {
                        throw new RuntimeException(sprintf('Composite identifiers not supported in "%s" through relation "%s" of "%s" used as identifier', $relatedResourceClass, $propertyName, $resourceClass));
                    }

                    $identifiers[$propertyName] = $this->propertyAccessor->getValue($relatedItem, $relatedPropertyName);
                }
            }

            if (!isset($identifiers[$propertyName])) {
                throw new RuntimeException(sprintf('No identifier found in "%s" through relation "%s" of "%s" used as identifier', $relatedResourceClass, $propertyName, $resourceClass));
            }
        }

        return $identifiers;
    }
}
