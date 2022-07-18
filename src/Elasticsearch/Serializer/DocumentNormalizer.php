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

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Document denormalizer for Elasticsearch.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class DocumentNormalizer extends ObjectNormalizer
{
    public const FORMAT = 'elasticsearch';

    private $resourceMetadataFactory;

    /**
     * @param ResourceMetadataCollectionFactoryInterface|ResourceMetadataFactoryInterface $resourceMetadataFactory
     */
    public function __construct($resourceMetadataFactory, ClassMetadataFactoryInterface $classMetadataFactory = null, NameConverterInterface $nameConverter = null, PropertyAccessorInterface $propertyAccessor = null, PropertyTypeExtractorInterface $propertyTypeExtractor = null, ClassDiscriminatorResolverInterface $classDiscriminatorResolver = null, callable $objectClassResolver = null, array $defaultContext = [])
    {
        parent::__construct($classMetadataFactory, $nameConverter, $propertyAccessor, $propertyTypeExtractor, $classDiscriminatorResolver, $objectClassResolver, $defaultContext);
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format, $context); // @phpstan-ignore-line symfony bc-layer
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
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
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        // prevent the use of lower priority normalizers (e.g. serializer.normalizer.object) for this format
        return self::FORMAT === $format;
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     *
     * @return array|string|int|float|bool|\ArrayObject|null
     */
    public function normalize($object, $format = null, array $context = [])
    {
        throw new LogicException(sprintf('%s is a write-only format.', self::FORMAT));
    }

    /**
     * Populates the resource identifier with the document identifier if not present in the original JSON document.
     */
    private function populateIdentifier(array $data, string $class): array
    {
        $identifier = 'id';
        $resourceMetadata = $this->resourceMetadataFactory->create($class);

        if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            $identifier = $resourceMetadata->getItemOperationAttribute('get', 'identifiers');
        } else {
            $operation = $resourceMetadata->getOperation();
            if ($operation instanceof HttpOperation) {
                $uriVariable = $operation->getUriVariables()[0] ?? null;

                if ($uriVariable) {
                    $identifier = $uriVariable->getIdentifiers()[0] ?? 'id';
                }
            }
        }

        $identifier = null === $this->nameConverter ? $identifier : $this->nameConverter->normalize($identifier, $class, self::FORMAT);

        if (!isset($data['_source'][$identifier])) {
            $data['_source'][$identifier] = $data['_id'];
        }

        return $data;
    }
}
