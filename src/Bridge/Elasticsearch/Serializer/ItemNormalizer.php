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

namespace ApiPlatform\Core\Bridge\Elasticsearch\Serializer;

use ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractorInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Item denormalizer for Elasticsearch.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ItemNormalizer extends ObjectNormalizer
{
    public const FORMAT = 'elasticsearch';

    private $identifierExtractor;

    public function __construct(IdentifierExtractorInterface $identifierExtractor, ClassMetadataFactoryInterface $classMetadataFactory = null, NameConverterInterface $nameConverter = null, PropertyAccessorInterface $propertyAccessor = null, PropertyTypeExtractorInterface $propertyTypeExtractor = null, ClassDiscriminatorResolverInterface $classDiscriminatorResolver = null, callable $objectClassResolver = null, array $defaultContext = [])
    {
        parent::__construct($classMetadataFactory, $nameConverter, $propertyAccessor, $propertyTypeExtractor, $classDiscriminatorResolver, $objectClassResolver, $defaultContext);

        $this->identifierExtractor = $identifierExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (\is_string($data['_id'] ?? null) && \is_array($data['_source'] ?? null)) {
            $data = $this->populateIdentifier($data, $class)['_source'];
        }

        return parent::denormalize($data, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        throw new RuntimeException(sprintf('%s is a read-only format.', self::FORMAT));
    }

    /**
     * Populates the resource identifier with the document identifier if not present in the original JSON document.
     */
    private function populateIdentifier(array $data, string $class): array
    {
        $identifier = $this->identifierExtractor->getIdentifierFromResourceClass($class);
        $identifier = null === $this->nameConverter ? $identifier : $this->nameConverter->normalize($identifier, $class, self::FORMAT);

        if (!isset($data['_source'][$identifier])) {
            $data['_source'][$identifier] = $data['_id'];
        }

        return $data;
    }
}
