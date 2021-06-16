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

namespace ApiPlatform\Core\Metadata\ResourceCollection\Factory;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class UriTemplateResourceCollectionMetadataFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $pathSegmentNameGenerator;
    private $decorated;

    public function __construct(PathSegmentNameGeneratorInterface $pathSegmentNameGenerator, ResourceCollectionMetadataFactoryInterface $decorated = null)
    {
        $this->pathSegmentNameGenerator = $pathSegmentNameGenerator;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceCollection
    {
        $parentResourceMetadata = [];
        if ($this->decorated) {
            try {
                $parentResourceMetadata = $this->decorated->create($resourceClass);
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        foreach ($parentResourceMetadata as $i => $resource) {
            if (!$resource->getUriTemplate()) {
                foreach ($resource->getOperations() as $key => $operation) {
                    if ($operation->isCollection()) {
                        $operation = $operation->withLinks($this->getLinks($parentResourceMetadata));
                    }

                    if ($operation->getUriTemplate()) {
                        continue;
                    }

                    $operation = $operation->withUriTemplate($this->generateUriTemplate($operation));
                    $operations = $resource->getOperations();
                    // Change the operation key
                    unset($operations[$key]);
                    $operations[sprintf('_api_%s_%s', $operation->uriTemplate, strtolower($operation->method))] = $operation;
                    $resource = $resource->withOperations($operations);
                }
            }

            $parentResourceMetadata[$i] = $resource;
        }

        return $parentResourceMetadata;
    }

    private function getLinks($resourceMetadata): array
    {
        $links = [];

        foreach ($resourceMetadata as $resource) {
            foreach ($resource->getOperations() as $operationName => $operation) {
                // About the routeName we can't do the link as we don't now enough
                if (!$operation->getRouteName() && false === $operation->isCollection() && Operation::METHOD_GET === $operation->getMethod()) {
                    $links[] = $operationName;
                }
            }
        }

        return $links;
    }

    private function generateUriTemplate(Operation $operation): string
    {
        $uriTemplate = $operation->getRoutePrefix() ?: '';
        if ($operation->isCollection()) {
            return sprintf('%s/%s.{_format}', $uriTemplate, $this->pathSegmentNameGenerator->getSegmentName($operation->getShortName()));
        }

        $uriTemplate = sprintf('%s/%s', $uriTemplate, $this->pathSegmentNameGenerator->getSegmentName($operation->getShortName()));
        foreach (array_keys($operation->getIdentifiers()) as $parameterName) {
            $uriTemplate .= sprintf('/{%s}', $parameterName);
        }

        return sprintf('%s.{_format}', $uriTemplate);
    }
}
