<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Nelmio\Extractor\AnnotationsProvider;

use Dunglas\ApiBundle\Api\Operation\OperationInterface;
use Dunglas\ApiBundle\Api\ResourceCollectionInterface;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Hydra\ApiDocumentationBuilderInterface;
use Dunglas\ApiBundle\Mapping\Factory\ClassMetadataFactoryInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\AnnotationsProviderInterface;
use Nelmio\ApiDocBundle\Parser\DunglasApiParser;
use Symfony\Component\HttpFoundation\Request;

/**
 * Creates Nelmio ApiDoc annotations for the api bundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiProvider implements AnnotationsProviderInterface
{
    /**
     * @var ResourceCollectionInterface
     */
    private $resourceCollection;
    /**
     * @var ApiDocumentationBuilderInterface
     */
    private $apiDocumentationBuilder;
    /**
     * @var ClassMetadataFactoryInterface
     */
    private $classMetadataFactory;

    public function __construct(
        ResourceCollectionInterface $resourceCollection,
        ApiDocumentationBuilderInterface $apiDocumentationBuilder,
        ClassMetadataFactoryInterface $classMetadataFactory
    ) {
        $this->resourceCollection = $resourceCollection;
        $this->apiDocumentationBuilder = $apiDocumentationBuilder;
        $this->classMetadataFactory = $classMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getAnnotations()
    {
        $annotations = [];
        $hydraDoc = $this->apiDocumentationBuilder->getApiDocumentation();
        $entrypointHydraDoc = $this->getResourceHydraDoc($hydraDoc, '#Entrypoint');

        /** @var ResourceInterface $resource */
        foreach ($this->resourceCollection as $resource) {
            $classMetadata = $this->classMetadataFactory->getMetadataFor($resource->getEntityClass());
            $prefixedShortName = ($iri = $classMetadata->getIri()) ? $iri : '#'.$resource->getShortName();
            $resourceHydraDoc = $this->getResourceHydraDoc($hydraDoc, $prefixedShortName);

            if ($hydraDoc) {
                foreach ($resource->getCollectionOperations() as $operation) {
                    $annotations[] = $this->getApiDoc(true, $resource, $operation, $resourceHydraDoc, $entrypointHydraDoc);
                }

                foreach ($resource->getItemOperations() as $operation) {
                    $annotations[] = $this->getApiDoc(false, $resource, $operation, $resourceHydraDoc);
                }
            }
        }

        return $annotations;
    }

    /**
     * Builds ApiDoc annotation from DunglasApiBundle data.
     *
     * @param bool               $collection
     * @param ResourceInterface  $resource
     * @param OperationInterface $operation
     * @param array              $resourceHydraDoc
     * @param array              $entrypointHydraDoc
     *
     * @return ApiDoc
     */
    private function getApiDoc(
        $collection,
        ResourceInterface $resource,
        OperationInterface $operation,
        array $resourceHydraDoc,
        array $entrypointHydraDoc = []
    ) {
        $method = $operation->getRoute()->getMethods()[0];

        if ($collection) {
            $operationHydraDoc = $this->getCollectionOperationHydraDoc($resource->getShortName(), $method, $entrypointHydraDoc);
        } else {
            $operationHydraDoc = $this->getOperationHydraDoc($operation->getRoute()->getMethods()[0], $resourceHydraDoc);
        }

        $route = $operation->getRoute();

        $data = [
            'resource' => $route->getPath(),
            'description' => $operationHydraDoc['hydra:title'],
            'resourceDescription' => $resourceHydraDoc['hydra:title'],
            'section' => $resourceHydraDoc['hydra:title'],
        ];

        $entityClass = $resource->getEntityClass();

        if (isset($operationHydraDoc['expects']) && 'owl:Nothing' !== $operationHydraDoc['expects']) {
            $data['input'] = sprintf('%s:%s', DunglasApiParser::IN_PREFIX, $entityClass);
        }

        if (isset($operationHydraDoc['returns']) && 'owl:Nothing' !== $operationHydraDoc['returns']) {
            $data['output'] = sprintf('%s:%s', DunglasApiParser::OUT_PREFIX, $entityClass);
        }

        if (Request::METHOD_GET === $method && $collection) {
            $data['filters'] = [];
            foreach ($resource->getFilters() as $filter) {
                foreach ($filter->getDescription($resource) as $name => $definition) {
                    $data['filters'][] = ['name' => $name] + $definition;
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
    private function getResourceHydraDoc(array $hydraApiDoc, $prefixedShortName)
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
    private function getOperationHydraDoc($method, array $hydraDoc)
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
    private function getCollectionOperationHydraDoc($shortName, $method, array $hydraEntrypointDoc)
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
