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
use ApiPlatform\Core\JsonLd\Util\BlankNodeIdentifiersGenerator;
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
    private $blankNodeIdentifiersGenerator;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, ContextBuilderInterface $contextBuilder, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ClassMetadataFactoryInterface $classMetadataFactory = null, IdentifiersExtractorInterface $identifiersExtractor = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, $identifiersExtractor);

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->contextBuilder = $contextBuilder;

        $this->blankNodeIdentifiersGenerator = new BlankNodeIdentifiersGenerator();
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

        $context = $this->addJsonLdDocumentContext($object, $context);

        $jsonLdIdentifier = $this->getJsonLdNodeIdentifier($object, $context);
        if ($this->hasIri($object)) {
            $context['iri'] = $jsonLdIdentifier;
        }

        $rawData = parent::normalize($object, $format, $context);
        if (!is_array($rawData)) {
            return $rawData;
        }

        $data['@id'] = $jsonLdIdentifier;
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
     *
     * @throws InvalidArgumentException
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        // Blank node identifiers cannot be used in denormalization
        // Denormalize into new object
        if (isset($data['@id']) && $this->isBlankNodeIdentifier($data['@id'])) {
            unset($data['@id']);
        }

        // Avoid issues with proxies if we populated the object
        if (isset($data['@id']) && !isset($context['object_to_populate'])) {
            if (isset($context['api_allow_update']) && true !== $context['api_allow_update']) {
                throw new InvalidArgumentException('Update is not allowed for this operation.');
            }

            $context['object_to_populate'] = $this->iriConverter->getItemFromIri($data['@id'], $context + ['fetch_data' => true]);
        }

        return parent::denormalize($data, $class, $format, $context);
    }

    /**
     * Adds information related to the JSON-LD document to the serializer context.
     *
     * @param object $object
     * @param array  $context
     *
     * @return array
     */
    private function addJsonLdDocumentContext($object, array $context)
    {
        $context['jsonld_document_root'] ?? $context['jsonld_document_root'] = spl_object_hash($object);

        return $context;
    }

    /**
     * Gets the identifier for a JSON-LD node.
     *
     * @param object $object
     * @param array  $context
     *
     * @return string
     */
    private function getJsonLdNodeIdentifier($object, array $context): string
    {
        return $this->hasIri($object) ? $this->iriConverter->getIriFromItem($object) : $this->blankNodeIdentifiersGenerator->getBlankNodeIdentifier($object, $context['jsonld_document_root']);
    }

    /**
     * Determines whether an IRI is a JSON-LD blank node identifier.
     *
     * @param string $iri
     *
     * @return bool
     */
    private function isBlankNodeIdentifier(string $iri): bool
    {
        return '_:' === substr($iri, 0, 2);
    }
}
