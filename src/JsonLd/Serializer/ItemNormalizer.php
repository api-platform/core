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

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\JsonLd\AnonymousContextBuilderInterface;
use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\Serializer\ContextTrait;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Util\ClassInfoTrait;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Converts between objects and array including JSON-LD and Hydra metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer extends AbstractItemNormalizer
{
    use ClassInfoTrait;
    use ContextTrait;
    use JsonLdContextTrait;

    public const FORMAT = 'jsonld';

    private $contextBuilder;

    public function __construct($resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, ResourceClassResolverInterface $resourceClassResolver, ContextBuilderInterface $contextBuilder, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ClassMetadataFactoryInterface $classMetadataFactory = null, array $defaultContext = [], iterable $dataTransformers = [], ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, null, false, $defaultContext, $dataTransformers, $resourceMetadataFactory, $resourceAccessChecker);

        if ($iriConverter instanceof LegacyIriConverterInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use an implementation of "%s" instead of "%s".', IriConverterInterface::class, LegacyIriConverterInterface::class));
        }

        $this->contextBuilder = $contextBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format, $context);
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
        $resourceClass = $this->getObjectClass($object);

        if ($outputClass = $this->getOutputClass($resourceClass, $context) && !isset($context[self::IS_TRANSFORMED_TO_SAME_CLASS])) {
            return parent::normalize($object, $format, $context);
        }

        // TODO: we should not remove the resource_class in the normalizeRawCollection as we would find out anyway that it's not the same as the requested one
        $previousResourceClass = $context['resource_class'] ?? null;
        $metadata = [];
        if ($isResourceClass = $this->resourceClassResolver->isResourceClass($resourceClass)) {
            $resourceClass = $this->resourceClassResolver->getResourceClass($object, $resourceClass);
            $context = $this->initContext($resourceClass, $context);
            $metadata = $this->addJsonLdContext($this->contextBuilder, $resourceClass, $context);
        } elseif ($this->contextBuilder instanceof AnonymousContextBuilderInterface) {
            if ($context['api_collection_sub_level'] ?? false) {
                unset($context['api_collection_sub_level']);
                $context['output']['genid'] = true;
                $context['output']['iri'] = null;
            }

            // We should improve what's behind the context creation, its probably more complicated then it should
            $metadata = $this->createJsonLdContext($this->contextBuilder, $object, $context);
        }

        if (isset($context['operation']) && $previousResourceClass !== $resourceClass) {
            unset($context['operation'], $context['operation_name']);
        }

        if ($this->iriConverter instanceof LegacyIriConverterInterface) {
            $iri = $this->iriConverter->getIriFromItem($object);
        } else {
            $iri = $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_PATH, $context['operation'] ?? null, $context);
        }

        if (isset($iri)) {
            $context['iri'] = $iri;
            $metadata['@id'] = $iri;
        }

        $context['api_normalize'] = true;

        $data = parent::normalize($object, $format, $context);
        if (!\is_array($data)) {
            return $data;
        }

        // TODO: remove in 3.0
        if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $metadata['@type'] = $resourceMetadata->getIri() ?: $resourceMetadata->getShortName();
        } elseif ($this->resourceMetadataFactory) {
            $operation = $context['operation'] ?? $this->resourceMetadataFactory->create($resourceClass)->getOperation();
            $types = $operation instanceof HttpOperation ? $operation->getTypes() : null;
            if (null === $types) {
                $types = [$operation->getShortName()];
            }
            $metadata['@type'] = 1 === \count($types) ? $types[0] : $types;
        }

        return $metadata + $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotNormalizableValueException
     *
     * @return mixed
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        // Avoid issues with proxies if we populated the object
        if (isset($data['@id']) && !isset($context[self::OBJECT_TO_POPULATE])) {
            if (true !== ($context['api_allow_update'] ?? true)) {
                throw new NotNormalizableValueException('Update is not allowed for this operation.');
            }

            $context[self::OBJECT_TO_POPULATE] = $this->iriConverter instanceof LegacyIriConverterInterface ? $this->iriConverter->getItemFromIri($data['@id'], $context + ['fetch_data' => true]) : $this->iriConverter->getResourceFromIri($data['@id'], $context + ['fetch_data' => true]);
        }

        return parent::denormalize($data, $class, $format, $context);
    }
}

class_alias(ItemNormalizer::class, \ApiPlatform\Core\JsonLd\Serializer\ItemNormalizer::class);
