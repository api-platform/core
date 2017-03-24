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

namespace ApiPlatform\Core\Bridge\NelmioApiDoc\Extractor\AnnotationsProvider;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser;
use ApiPlatform\Core\Bridge\Symfony\Routing\OperationMethodResolverInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\AnnotationsProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates Nelmio ApiDoc annotations for the api platform.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class ApiPlatformProvider implements AnnotationsProviderInterface
{
    private $resourceNameCollectionFactory;
    private $documentationNormalizer;
    private $resourceMetadataFactory;
    private $filters;
    private $operationMethodResolver;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, NormalizerInterface $documentationNormalizer, ResourceMetadataFactoryInterface $resourceMetadataFactory, FilterCollection $filters, OperationMethodResolverInterface $operationMethodResolver)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->documentationNormalizer = $documentationNormalizer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->filters = $filters;
        $this->operationMethodResolver = $operationMethodResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getAnnotations(): array
    {
        $hydraDoc = $this->documentationNormalizer->normalize(new Documentation($this->resourceNameCollectionFactory->create()));
        $entrypointHydraDoc = $this->getResourceHydraDoc($hydraDoc, '#Entrypoint');

        if (empty($hydraDoc) || null === $entrypointHydraDoc) {
            return [];
        }

        $annotations = [];
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            $prefixedShortName = ($iri = $resourceMetadata->getIri()) ? $iri : '#'.$resourceMetadata->getShortName();
            $resourceHydraDoc = $this->getResourceHydraDoc($hydraDoc, $prefixedShortName);
            if (null === $resourceHydraDoc) {
                continue;
            }

            if (null !== $collectionOperations = $resourceMetadata->getCollectionOperations()) {
                foreach ($collectionOperations as $operationName => $operation) {
                    $annotations[] = $this->getApiDoc(true, $resourceClass, $resourceMetadata, $operationName, $resourceHydraDoc, $entrypointHydraDoc);
                }
            }

            if (null !== $itemOperations = $resourceMetadata->getItemOperations()) {
                foreach ($itemOperations as $operationName => $operation) {
                    $annotations[] = $this->getApiDoc(false, $resourceClass, $resourceMetadata, $operationName, $resourceHydraDoc);
                }
            }
        }

        return $annotations;
    }

    /**
     * Builds ApiDoc annotation from ApiPlatform data.
     *
     * @param bool             $collection
     * @param string           $resourceClass
     * @param ResourceMetadata $resourceMetadata
     * @param string           $operationName
     * @param array            $resourceHydraDoc
     * @param array            $entrypointHydraDoc
     *
     * @return ApiDoc
     */
    private function getApiDoc(bool $collection, string $resourceClass, ResourceMetadata $resourceMetadata, string $operationName, array $resourceHydraDoc, array $entrypointHydraDoc = []): ApiDoc
    {
        if ($collection) {
            $method = $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);
            $route = $this->operationMethodResolver->getCollectionOperationRoute($resourceClass, $operationName);
            $operationHydraDoc = $this->getCollectionOperationHydraDoc($resourceMetadata->getShortName(), $method, $entrypointHydraDoc);
        } else {
            $method = $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName);
            $route = $this->operationMethodResolver->getItemOperationRoute($resourceClass, $operationName);
            $operationHydraDoc = $this->getOperationHydraDoc($method, $resourceHydraDoc);
        }

        $data = [
            'resource' => $route->getPath(),
            'description' => $operationHydraDoc['hydra:title'],
            'resourceDescription' => $resourceHydraDoc['hydra:title'],
            'section' => $resourceHydraDoc['hydra:title'],
        ];

        if (isset($operationHydraDoc['expects']) && 'owl:Nothing' !== $operationHydraDoc['expects']) {
            $data['input'] = sprintf('%s:%s:%s', ApiPlatformParser::IN_PREFIX, $resourceClass, $operationName);
        }

        if (isset($operationHydraDoc['returns']) && 'owl:Nothing' !== $operationHydraDoc['returns']) {
            $data['output'] = sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, $resourceClass, $operationName);
        }

        if ($collection && Request::METHOD_GET === $method) {
            $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

            $data['filters'] = [];
            foreach ($this->filters as $filterName => $filter) {
                if (in_array($filterName, $resourceFilters, true)) {
                    foreach ($filter->getDescription($resourceClass) as $name => $definition) {
                        $data['filters'][] = ['name' => $name] + $definition;
                    }
                }
            }
        }

        $apiDoc = new ApiDoc($data);
        $apiDoc->setRoute($route);

        return $apiDoc;
    }

    /**
     * Gets Hydra documentation for the given resource.
     *
     * @param array  $hydraApiDoc
     * @param string $prefixedShortName
     *
     * @return array|null
     */
    private function getResourceHydraDoc(array $hydraApiDoc, string $prefixedShortName)
    {
        foreach ($hydraApiDoc['hydra:supportedClass'] as $supportedClass) {
            if ($supportedClass['@id'] === $prefixedShortName) {
                return $supportedClass;
            }
        }
    }

    /**
     * Gets the Hydra documentation of a given operation.
     *
     * @param string $method
     * @param array  $hydraDoc
     *
     * @return array|null
     */
    private function getOperationHydraDoc(string $method, array $hydraDoc)
    {
        foreach ($hydraDoc['hydra:supportedOperation'] as $supportedOperation) {
            if ($supportedOperation['hydra:method'] === $method) {
                return $supportedOperation;
            }
        }
    }

    /**
     * Gets the Hydra documentation for the collection operation.
     *
     * @param string $shortName
     * @param string $method
     * @param array  $hydraEntrypointDoc
     *
     * @return array|null
     */
    private function getCollectionOperationHydraDoc(string $shortName, string $method, array $hydraEntrypointDoc)
    {
        $propertyName = '#Entrypoint/'.lcfirst($shortName);

        foreach ($hydraEntrypointDoc['hydra:supportedProperty'] as $supportedProperty) {
            $hydraProperty = $supportedProperty['hydra:property'];
            if ($hydraProperty['@id'] === $propertyName) {
                return $this->getOperationHydraDoc($method, $hydraProperty);
            }
        }
    }
}
