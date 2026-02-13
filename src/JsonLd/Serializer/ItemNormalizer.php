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

use ApiPlatform\JsonLd\AnonymousContextBuilderInterface;
use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\Serializer\ContextTrait;
use ApiPlatform\Serializer\OperationResourceClassResolverInterface;
use ApiPlatform\Serializer\TagCollectorInterface;
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
    private const JSONLD_KEYWORDS = [
        '@context',
        '@direction',
        '@graph',
        '@id',
        '@import',
        '@included',
        '@index',
        '@json',
        '@language',
        '@list',
        '@nest',
        '@none',
        '@prefix',
        '@propagate',
        '@protected',
        '@reverse',
        '@set',
        '@type',
        '@value',
        '@version',
        '@vocab',
    ];

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, private readonly ContextBuilderInterface $contextBuilder, ?PropertyAccessorInterface $propertyAccessor = null, ?NameConverterInterface $nameConverter = null, ?ClassMetadataFactoryInterface $classMetadataFactory = null, array $defaultContext = [], ?ResourceAccessCheckerInterface $resourceAccessChecker = null, protected ?TagCollectorInterface $tagCollector = null, private ?OperationMetadataFactoryInterface $operationMetadataFactory = null, ?OperationResourceClassResolverInterface $operationResourceResolver = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, $defaultContext, $resourceMetadataCollectionFactory, $resourceAccessChecker, $tagCollector, $operationResourceResolver);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedTypes(?string $format): array
    {
        return self::FORMAT === $format ? parent::getSupportedTypes($format) : [];
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $resourceClass = $this->getObjectClass($data);
        $outputClass = $this->getOutputClass($context);

        if ($outputClass) {
            if ($context['item_uri_template'] ?? null) {
                // When both output and item_uri_template are present, temporarily remove
                // item_uri_template so the output re-dispatch produces the correct @type
                // from the output class (not from the item_uri_template operation).
                $itemUriTemplate = $context['item_uri_template'];
                unset($context['item_uri_template']);
                $originalData = $data;
                $data = parent::normalize($data, $format, $context);
                if (\is_array($data)) {
                    try {
                        $context['item_uri_template'] = $itemUriTemplate;
                        $data['@id'] = $this->iriConverter->getIriFromResource($originalData, UrlGeneratorInterface::ABS_PATH, null, $context);
                    } catch (\Exception) {
                    }
                }

                return $data;
            }

            return parent::normalize($data, $format, $context);
        }

        // TODO: we should not remove the resource_class in the normalizeRawCollection as we would find out anyway that it's not the same as the requested one
        $previousResourceClass = $context['resource_class'] ?? null;
        $metadata = [];
        if ($isResourceClass = $this->resourceClassResolver->isResourceClass($resourceClass) && (null === $previousResourceClass || $this->resourceClassResolver->isResourceClass($previousResourceClass))) {
            $resourceClass = $this->resourceClassResolver->getResourceClass($data, $previousResourceClass);
            $context = $this->initContext($resourceClass, $context);
            $metadata = $this->addJsonLdContext($this->contextBuilder, $resourceClass, $context);
        } elseif ($this->contextBuilder instanceof AnonymousContextBuilderInterface) {
            if ($context['api_collection_sub_level'] ?? false) {
                unset($context['api_collection_sub_level']);
                $context['output']['gen_id'] ??= true;
                $context['output']['iri'] = null;
            }

            if (isset($context['item_uri_template']) && $this->operationMetadataFactory) {
                $itemOp = $this->operationMetadataFactory->create($context['item_uri_template']);
                // Use resource-level shortName for @type, not operation-specific shortName
                try {
                    $itemResourceShortName = $this->resourceMetadataCollectionFactory->create($itemOp->getClass())[0]->getShortName();
                    $context['output']['operation'] = $itemOp->withShortName($itemResourceShortName);
                } catch (\Exception) {
                    $context['output']['operation'] = $itemOp;
                }
            } elseif ($this->resourceClassResolver->isResourceClass($resourceClass)) {
                $context['output']['operation'] = $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation();
            }

            // We should improve what's behind the context creation, its probably more complicated then it should
            $metadata = $this->createJsonLdContext($this->contextBuilder, $data, $context);
        }

        // Special case: non-resource got serialized and contains a resource therefore we need to reset part of the context
        if ($previousResourceClass !== $resourceClass && $resourceClass !== $outputClass) {
            unset($context['operation'], $context['operation_name'], $context['output']);
        }

        if (true === ($context['output']['gen_id'] ?? true) && true === ($context['force_iri_generation'] ?? true) && $iri = $this->iriConverter->getIriFromResource($data, UrlGeneratorInterface::ABS_PATH, $context['operation'] ?? null, $context)) {
            $context['iri'] = $iri;
            $metadata['@id'] = $iri;
        }

        $context['api_normalize'] = true;

        $normalizedData = parent::normalize($data, $format, $context);
        if (!\is_array($normalizedData)) {
            return $normalizedData;
        }

        $operation = $context['operation'] ?? null;

        if ($this->operationMetadataFactory && isset($context['item_uri_template']) && !$operation) {
            $operation = $this->operationMetadataFactory->create($context['item_uri_template']);
        }

        if ($isResourceClass && !$operation) {
            $operation = $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation();
        }

        if (!isset($metadata['@type']) && $operation) {
            $types = $operation instanceof HttpOperation ? $operation->getTypes() : null;
            if (null === $types) {
                // TODO: 5.x break on this as this looks wrong, CollectionReferencingItem returns an IRI that point through
                // ItemReferencedInCollection but it returns a CollectionReferencingItem therefore we should use the current
                // object's class Type and not rely on operation ?
                // Use resource-level shortName to avoid operation-specific overrides
                $typeClass = $isResourceClass ? $resourceClass : ($operation->getClass() ?? $resourceClass);
                try {
                    $types = [$this->resourceMetadataCollectionFactory->create($typeClass)[0]->getShortName()];
                } catch (\Exception) {
                    $types = [$operation->getShortName()];
                }
            }
            $metadata['@type'] = 1 === \count($types) ? $types[0] : $types;
        }

        return $metadata + $normalizedData;
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

    protected function getAllowedAttributes(string|object $classOrObject, array $context, bool $attributesAsString = false): array|bool
    {
        $allowedAttributes = parent::getAllowedAttributes($classOrObject, $context, $attributesAsString);
        if (\is_array($allowedAttributes) && ($context['api_denormalize'] ?? false)) {
            $allowedAttributes = array_merge($allowedAttributes, self::JSONLD_KEYWORDS);
        }

        return $allowedAttributes;
    }
}
