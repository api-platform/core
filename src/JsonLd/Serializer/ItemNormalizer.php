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

use ApiPlatform\Core\Api\ContextAwareIriConverterInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Serializer\ContextTrait;
use ApiPlatform\Core\Util\ClassInfoTrait;
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

    public function __construct($resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, ContextBuilderInterface $contextBuilder, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ClassMetadataFactoryInterface $classMetadataFactory = null, array $defaultContext = [], iterable $dataTransformers = [], ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, null, false, $defaultContext, $dataTransformers, $resourceMetadataFactory, $resourceAccessChecker);

        if ($iriConverter instanceof IriConverterInterface) {
            @trigger_error(sprintf('The %s interface is deprecated since version 2.7 and will be removed in 3.0. Provide an implementation of %s instead.', IriConverterInterface::class, ContextAwareIriConverterInterface::class), \E_USER_DEPRECATED);
        }

        $this->contextBuilder = $contextBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $objectClass = $this->getObjectClass($object);
        $outputClass = $this->getOutputClass($objectClass, $context);
        if (null !== $outputClass && !isset($context[self::IS_TRANSFORMED_TO_SAME_CLASS])) {
            return parent::normalize($object, $format, $context);
        }

        $context = $this->initContext($context['resource_class'], $context);
        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null);
        $metadata = $this->addJsonLdContext($this->contextBuilder, $resourceClass, $context);

        if ($this->iriConverter instanceof ContextAwareIriConverterInterface) {
            // Note that this responsability should be transfered to the ResourceMetadataFactory
            // it would only be possible if the Metadata defines the output of the Custom Controller and that the output is also a Resource
            // this is the only case where it happens (or a Provider returning another class then the one requested)
            if ($this->resourceMetadataFactory instanceof ResourceCollectionMetadataFactoryInterface && $resourceClass !== $context['resource_class']) {
                foreach ($this->resourceMetadataFactory->create($resourceClass) as $resource) {
                    // Trigger a deprecation because:
                    // En gros une Resource peut avoir une Opération qui dans son controlleur retourne une autre Resource ! 
                    //
                    // Dans ce cas, je dois retrouver les Metadonnées liées a celui ci sauf que j'ai aucun moyen de le savoir en amont. Pour toi c'est la responsabilité:
                    //
                    // - Du SerializeListener
                    // - Du JsonLd\ItemNormalizer (doit trouver le @id qui correspond a l'autre resource) 
                    // - Du IriConverter
                    //
                    // Sachant qu'en vrai on devrait totalement déprecier ca car ce sera faisable avec:
                    // #[Resource(operations=[new Post('/payments/{id}/void', identifiers: ['id' => [Payment::class, 'id']])])
                    // class VoidPayment {}
                    foreach ($resource->getOperations() as $operation) {
                        if ($operation->getMethod() === Operation::METHOD_GET && !$operation->isCollection()) {
                            $context['links'] = $operation->getLinks();
                            break 2;
                        }
                    }
                }
            }

            $iriContext = isset($context['links'][0]) ? ['operation_name' => $context['links'][0][0], 'identifiers' => $context['links'][0][1]] + $context : $context;
            $iri = $this->iriConverter->getIriFromItem($object, UrlGeneratorInterface::ABS_PATH, $iriContext);
        } else {
            $iri = $this->iriConverter->getIriFromItem($object);
        }

        $context['iri'] = $iri;
        $metadata['@id'] = $iri;
        $context['api_normalize'] = true;

        $data = parent::normalize($object, $format, $context);
        if (!\is_array($data)) {
            return $data;
        }

        // TODO: remove in 3.0
        if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $metadata['@type'] = $resourceMetadata->getIri() ?: $resourceMetadata->getShortName();
        } else {
            // TODO: multiple types?
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass)[0];
            $metadata['@type'] = $resourceMetadata->getTypes()[0] ?: $resourceMetadata->getShortName();
        }

        return $metadata + $data;
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
     *
     * @throws NotNormalizableValueException
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        // Avoid issues with proxies if we populated the object
        if (isset($data['@id']) && !isset($context[self::OBJECT_TO_POPULATE])) {
            if (true !== ($context['api_allow_update'] ?? true)) {
                throw new NotNormalizableValueException('Update is not allowed for this operation.');
            }

            $context[self::OBJECT_TO_POPULATE] = $this->iriConverter->getItemFromIri($data['@id'], $context + ['fetch_data' => true]);
        }

        return parent::denormalize($data, $class, $format, $context);
    }
}
