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

namespace ApiPlatform\Hydra\Serializer;

use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\JsonLd\Serializer\HydraPrefixTrait;
use ApiPlatform\JsonLd\Serializer\JsonLdContextTrait;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Serializer\AbstractCollectionNormalizer;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;

/**
 * This normalizer handles collections.
 *
 * @author Kevin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class CollectionNormalizer extends AbstractCollectionNormalizer
{
    use HydraPrefixTrait;
    use JsonLdContextTrait;

    public const FORMAT = 'jsonld';
    public const IRI_ONLY = 'iri_only';
    private array $defaultContext = [
        self::IRI_ONLY => false,
    ];

    public function __construct(private readonly ContextBuilderInterface $contextBuilder, ResourceClassResolverInterface $resourceClassResolver, private readonly IriConverterInterface $iriConverter, readonly ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null, array $defaultContext = [])
    {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);

        if ($resourceMetadataCollectionFactory) {
            trigger_deprecation('api-platform/core', '3.0', \sprintf('Injecting "%s" within "%s" is not needed anymore and this dependency will be removed in 4.0.', ResourceMetadataCollectionFactoryInterface::class, self::class));
        }

        parent::__construct($resourceClassResolver, '');
    }

    /**
     * Gets the pagination data.
     */
    protected function getPaginationData(iterable $object, array $context = []): array
    {
        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class']);
        $hydraPrefix = $this->getHydraPrefix($context + $this->defaultContext);
        // This adds "jsonld_has_context" by reference, we moved the code to this class.
        // To follow a note I wrote in the ItemNormalizer, we need to change the JSON-LD context generation as it is more complicated then it should.
        $data = $this->addJsonLdContext($this->contextBuilder, $resourceClass, $context);
        $data['@id'] = $this->iriConverter->getIriFromResource($resourceClass, UrlGeneratorInterface::ABS_PATH, $context['operation'] ?? null, $context);
        $data['@type'] = $hydraPrefix.'Collection';

        if ($object instanceof PaginatorInterface) {
            $data[$hydraPrefix.'totalItems'] = $object->getTotalItems();
        }

        if (\is_array($object) || ($object instanceof \Countable && !$object instanceof PartialPaginatorInterface)) {
            $data[$hydraPrefix.'totalItems'] = \count($object);
        }

        return $data;
    }

    /**
     * Gets items data.
     */
    protected function getItemsData(iterable $object, ?string $format = null, array $context = []): array
    {
        $hydraPrefix = $this->getHydraPrefix($context + $this->defaultContext);
        $data = [$hydraPrefix.'member' => []];
        $iriOnly = $context[self::IRI_ONLY] ?? $this->defaultContext[self::IRI_ONLY];

        foreach ($object as $obj) {
            if ($iriOnly) {
                $data[$hydraPrefix.'member'][] = $this->iriConverter->getIriFromResource($obj);
            } else {
                $data[$hydraPrefix.'member'][] = $this->normalizer->normalize($obj, $format, $context + ['jsonld_has_context' => true]);
            }
        }

        return $data;
    }

    protected function initContext(string $resourceClass, array $context): array
    {
        $context = parent::initContext($resourceClass, $context);
        $context['api_collection_sub_level'] = true;

        return $context;
    }
}
