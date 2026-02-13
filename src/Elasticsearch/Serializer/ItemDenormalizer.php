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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\Serializer\TagCollectorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Item denormalizer for Elasticsearch.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ItemDenormalizer extends AbstractItemNormalizer
{
    public const FORMAT = 'elasticsearch';

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
        ?TagCollectorInterface $tagCollector = null,
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
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        // Handle Elasticsearch document structure with _id and _source
        if (\is_array($data) && \is_string($data['_id'] ?? null) && \is_array($data['_source'] ?? null)) {
            $data = $this->populateIdentifier($data, $type)['_source'];
        }

        return parent::denormalize($data, $type, $format, $context);
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
    public function getSupportedTypes(?string $format): array
    {
        return self::FORMAT === $format ? ['object' => true] : [];
    }

    /**
     * {@inheritdoc}
     *
     * For Elasticsearch, we always allow nested documents because that's how ES stores and returns data.
     */
    protected function denormalizeRelation(string $attributeName, ApiProperty $propertyMetadata, string $className, mixed $value, ?string $format, array $context): ?object
    {
        // For Elasticsearch, always allow nested documents
        $context['api_allow_update'] = true;

        if (!$this->serializer instanceof DenormalizerInterface) {
            throw new LogicException(\sprintf('The injected serializer must be an instance of "%s".', DenormalizerInterface::class));
        }

        $item = $this->serializer->denormalize($value, $className, $format, $context);
        if (!\is_object($item) && null !== $item) {
            throw new \UnexpectedValueException('Expected item to be an object or null.');
        }

        return $item;
    }
}
