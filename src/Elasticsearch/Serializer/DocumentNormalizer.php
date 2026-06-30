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
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Document denormalizer for Elasticsearch.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class DocumentNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    public const FORMAT = 'elasticsearch';

    private readonly ObjectNormalizer $decoratedNormalizer;

    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        ?ClassMetadataFactoryInterface $classMetadataFactory = null,
        private readonly ?NameConverterInterface $nameConverter = null,
        ?PropertyAccessorInterface $propertyAccessor = null,
        ?PropertyTypeExtractorInterface $propertyTypeExtractor = null,
        ?ClassDiscriminatorResolverInterface $classDiscriminatorResolver = null,
        ?callable $objectClassResolver = null,
        array $defaultContext = [],
        private readonly ?PropertyMetadataFactoryInterface $propertyMetadataFactory = null,
    ) {
        $this->decoratedNormalizer = new ObjectNormalizer($classMetadataFactory, $nameConverter, $propertyAccessor, $propertyTypeExtractor, $classDiscriminatorResolver, $objectClassResolver, $defaultContext);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $this->decoratedNormalizer->supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (\is_string($data['_id'] ?? null) && \is_array($data['_source'] ?? null)) {
            $data = $this->populateIdentifier($data, $type)['_source'];
        }

        return $this->decoratedNormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        // prevent the use of lower priority normalizers (e.g. serializer.normalizer.object) for this format
        return self::FORMAT === $format;
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        throw new LogicException(\sprintf('%s is a write-only format.', self::FORMAT));
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

        $sourceKey = null === $this->nameConverter ? $identifier : $this->nameConverter->normalize($identifier, $class, self::FORMAT);

        if (!isset($data['_source'][$sourceKey])) {
            $data['_source'][$sourceKey] = $this->coerceIdentifier($class, $identifier, $data['_id']);
        }

        return $data;
    }

    /**
     * Elasticsearch always exposes the document identifier (`_id`) as a string. When the resource
     * identifier is declared as an int, casting it back avoids a type mismatch in the inner
     * ObjectNormalizer. String identifiers (e.g. UUIDs) are left untouched.
     */
    private function coerceIdentifier(string $class, string $identifier, string $value): int|string
    {
        if (null === $this->propertyMetadataFactory || !is_numeric($value)) {
            return $value;
        }

        $nativeType = $this->propertyMetadataFactory->create($class, $identifier)->getNativeType();

        if ($nativeType?->isIdentifiedBy(TypeIdentifier::INT) && !$nativeType->isIdentifiedBy(TypeIdentifier::STRING)) {
            return (int) $value;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->decoratedNormalizer->setSerializer($serializer);
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedTypes(?string $format): array
    {
        return self::FORMAT === $format ? ['object' => true] : [];
    }
}
