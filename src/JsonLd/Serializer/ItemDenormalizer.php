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

namespace ApiPlatform\JsonLd\Serializer;

use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\Serializer\TagCollectorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Converts JSON-LD data to objects (denormalization only).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemDenormalizer extends AbstractItemNormalizer
{
    use ItemNormalizerTrait;

    public function __construct(
        ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        PropertyMetadataFactoryInterface $propertyMetadataFactory,
        IriConverterInterface $iriConverter,
        ResourceClassResolverInterface $resourceClassResolver,
        ?PropertyAccessorInterface $propertyAccessor = null,
        ?NameConverterInterface $nameConverter = null,
        ?ClassMetadataFactoryInterface $classMetadataFactory = null,
        array $defaultContext = [],
        ?ResourceAccessCheckerInterface $resourceAccessChecker = null,
        protected ?TagCollectorInterface $tagCollector = null,
    ) {
        parent::__construct(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $iriConverter,
            $resourceClassResolver,
            $propertyAccessor,
            $nameConverter,
            $classMetadataFactory,
            $defaultContext,
            $resourceMetadataCollectionFactory,
            $resourceAccessChecker,
            $tagCollector
        );
    }

    /**
     * {@inheritdoc}
     *
     * This denormalizer only handles denormalization. Use ItemNormalizer for normalization.
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotNormalizableValueException
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        // Avoid issues with proxies if we populated the object
        if (isset($data['@id']) && !isset($context[self::OBJECT_TO_POPULATE])) {
            if (true !== ($context['api_allow_update'] ?? true)) {
                throw new NotNormalizableValueException('Update is not allowed for this operation.');
            }

            try {
                $context[self::OBJECT_TO_POPULATE] = $this->iriConverter->getResourceFromIri($data['@id'], $context + ['fetch_data' => true], $context['operation'] ?? null);
            } catch (ItemNotFoundException $e) {
                $operation = $context['operation'] ?? null;

                if (!('PUT' === $operation?->getMethod() && ($operation->getExtraProperties()['standard_put'] ?? true))) {
                    throw $e;
                }
            }
        }

        return parent::denormalize($data, $type, $format, $context);
    }
}
