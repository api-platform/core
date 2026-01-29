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

namespace ApiPlatform\Elasticsearch\Serializer;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Document denormalizer for Elasticsearch.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class DocumentDenormalizer implements DenormalizerInterface, SerializerAwareInterface
{
    public const FORMAT = 'elasticsearch';

    private readonly ObjectNormalizer $decoratedDenormalizer;

    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        ?ClassMetadataFactoryInterface $classMetadataFactory = null,
        private readonly ?NameConverterInterface $nameConverter = null,
        ?PropertyAccessorInterface $propertyAccessor = null,
        ?PropertyTypeExtractorInterface $propertyTypeExtractor = null,
        ?ClassDiscriminatorResolverInterface $classDiscriminatorResolver = null,
        ?callable $objectClassResolver = null,
        array $defaultContext = [],
    ) {
        $this->decoratedDenormalizer = new ObjectNormalizer($classMetadataFactory, $nameConverter, $propertyAccessor, $propertyTypeExtractor, $classDiscriminatorResolver, $objectClassResolver, $defaultContext);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $this->decoratedDenormalizer->supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (\is_string($data['_id'] ?? null) && \is_array($data['_source'] ?? null)) {
            $data = $this->populateIdentifier($data, $type)['_source'];
        }

        return $this->decoratedDenormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * Populates the resource identifier with the document identifier if not present in the original JSON document.
     */
    private function populateIdentifier(array $data, string $class): array
    {
        $identifier = 'id';
        $resourceMetadata = $this->resourceMetadataCollectionFactory->create($class);

        $operation = $resourceMetadata->getOperation();
        if ($operation instanceof HttpOperation) {
            $uriVariable = $operation->getUriVariables()[0] ?? null;

            if ($uriVariable) {
                $identifier = $uriVariable->getIdentifiers()[0] ?? 'id';
            }
        }

        $identifier = null === $this->nameConverter ? $identifier : $this->nameConverter->normalize($identifier, $class, self::FORMAT);

        if (!isset($data['_source'][$identifier])) {
            $data['_source'][$identifier] = $data['_id'];
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->decoratedDenormalizer->setSerializer($serializer);
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedTypes(?string $format): array
    {
        return self::FORMAT === $format ? ['object' => true] : [];
    }
}
