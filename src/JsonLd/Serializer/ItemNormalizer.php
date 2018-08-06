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

namespace ApiPlatform\Core\JsonLd\Serializer;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Serializer\ContextTrait;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Converts between objects and array including JSON-LD and Hydra metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer extends AbstractItemNormalizer
{
    use ContextTrait;
    use JsonLdContextTrait;

    const FORMAT = 'jsonld';

    private $resourceMetadataFactory;
    private $contextBuilder;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, ContextBuilderInterface $contextBuilder, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ClassMetadataFactoryInterface $classMetadataFactory = null, IdentifiersExtractorInterface $identifiersExtractor = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, null, false, $identifiersExtractor);

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->contextBuilder = $contextBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null, true);
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $data = $this->addJsonLdContext($this->contextBuilder, $resourceClass, $context);

        // Use resolved resource class instead of given resource class to support multiple inheritance child types
        $context['resource_class'] = $resourceClass;
        $context['iri'] = $this->iriConverter->getIriFromItem($object);

        $rawData = parent::normalize($object, $format, $context);
        if (!\is_array($rawData)) {
            return $rawData;
        }

        $data['@id'] = $context['iri'];
        $data['@type'] = $resourceMetadata->getIri() ?: $resourceMetadata->getShortName();

        return $data + $rawData;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format);
    }

    /**
     * {@inheritdoc}
     */
    protected function setObjectToPopulate($data, string $class, array &$context)
    {
        try {
            parent::setObjectToPopulate($data, $class, $context);

            return;
        } catch (InvalidArgumentException $e) {
        }

        // https://github.com/api-platform/core/issues/857
        $identifiers = $this->identifiersExtractor->getIdentifiersFromResourceClass($class);
        $identifiersData = \array_intersect_key($data, array_flip($identifiers));
        if (0 === \count($identifiersData)) {
            throw $e;
        }

        // TODO: use $this->iriConverter->getIriFromPlainIdentifier() once https://github.com/api-platform/core/pull/1837 is merged.
        $context[self::OBJECT_TO_POPULATE] = $this->iriConverter->getItemFromIri(
            sprintf(
                '%s/%s',
                $this->iriConverter->getIriFromResourceClass($context['resource_class']),
                implode(';', $identifiersData)
            ),
            $context + ['fetch_data' => true]
        );
    }

    protected function getIdentifiersForDenormalization(string $class): array
    {
        return ['@id'];
    }
}
