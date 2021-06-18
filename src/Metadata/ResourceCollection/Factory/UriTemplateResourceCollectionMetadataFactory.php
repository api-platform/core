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
                    if ($operation->getUriTemplate()) {
                        continue;
                    }

                    $operations = iterator_to_array($resource->getOperations());

                    if ($routeName = $operation->getRouteName()) {
                        unset($operations[$key]);
                        $operations[$routeName] = $operation;
                        $resource = $resource->withOperations($operations);
                        continue;
                    }

                    $operation = $operation->withUriTemplate($this->generateUriTemplate($operation));
                    // Change the operation key
                    unset($operations[$key]);
                    $operations[sprintf('_api_%s_%s', $operation->getUriTemplate(), strtolower($operation->getMethod()))] = $operation;
                    $resource = $resource->withOperations($operations);
                }
            }

            $parentResourceMetadata[$i] = $resource;
        }

        return $parentResourceMetadata;
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
