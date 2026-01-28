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

namespace ApiPlatform\JsonApi\Serializer;

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
 * Converts JSON:API data to objects (denormalization only).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ItemDenormalizer extends AbstractItemNormalizer
{
    use ItemNormalizerTrait;

    public function __construct(
        PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        PropertyMetadataFactoryInterface $propertyMetadataFactory,
        IriConverterInterface $iriConverter,
        ResourceClassResolverInterface $resourceClassResolver,
        ?PropertyAccessorInterface $propertyAccessor = null,
        ?NameConverterInterface $nameConverter = null,
        ?ClassMetadataFactoryInterface $classMetadataFactory = null,
        array $defaultContext = [],
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null,
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
     * @throws NotNormalizableValueException
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        // Avoid issues with proxies if we populated the object
        if (!isset($context[self::OBJECT_TO_POPULATE]) && isset($data['data']['id'])) {
            if (true !== ($context['api_allow_update'] ?? true)) {
                throw new NotNormalizableValueException('Update is not allowed for this operation.');
            }

            $context[self::OBJECT_TO_POPULATE] = $this->iriConverter->getResourceFromIri(
                $data['data']['id'],
                $context + ['fetch_data' => false]
            );
        }

        // Merge attributes and relationships, into format expected by the parent normalizer
        $dataToDenormalize = array_merge(
            $data['data']['attributes'] ?? [],
            $data['data']['relationships'] ?? []
        );

        return parent::denormalize(
            $dataToDenormalize,
            $type,
            $format,
            $context
        );
    }
}
