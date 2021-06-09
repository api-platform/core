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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Symfony\Component\Routing\Route;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class UriTemplateResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private $pathSegmentNameGenerator;
    private $decorated;

    public function __construct(PathSegmentNameGeneratorInterface $pathSegmentNameGenerator, ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
        $this->pathSegmentNameGenerator = $pathSegmentNameGenerator;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        foreach ($resourceMetadataCollection as $i => $resource) {
            if ($resource->getUriTemplate()) {
                $resourceMetadataCollection[$i] = $resource->withExtraProperties($resource->getExtraProperties() + ['user_defined_uri_template' => true]);
            }

            $operations = $resource->getOperations();

            foreach ($resource->getOperations() as $key => $operation) {
                if ($operation->getUriTemplate()) {
                    $route = (new Route($operation->getUriTemplate()))->compile();
                    $variables = array_filter($route->getPathVariables(), function ($v) {
                        return '_format' !== $v;
                    });

                    // TODO: remove in 3.0, guess identifiers
                    if (\count($variables) && !$operation->getIdentifiers()) {
                        trigger_deprecation('api-platform/core', '2.7', sprintf('In the uriTemplate "%s" we detected parameters that are not matched inside identifiers. Not providing identifiers on the operation will not be possible in 3.0.', $operation->getUriTemplate()));
                        $identifiers = [];
                        foreach ($variables as $variable) {
                            $identifiers[$variable] = [$resourceClass, $variable];
                        }

                        $operation = $operation->withIdentifiers($identifiers);
                    }

                    $operation = $operation->withExtraProperties($operation->getExtraProperties() + ['user_defined_uri_template' => true]);
                    $operations->add($key, $operation);
                    continue;
                }

                if ($routeName = $operation->getRouteName()) {
                    $operations->remove($key)->add($routeName, $operation);
                    continue;
                }

                $operation = $operation->withUriTemplate($this->generateUriTemplate($operation));
                $operationName = $operation->getName() ?: sprintf('_api_%s_%s%s', $operation->getUriTemplate(), strtolower($operation->getMethod()), $operation->isCollection() ? '_collection' : '');

                // Change the operation key
                $operations->remove($key)
                           ->add($operationName, $operation);
            }

            $resource = $resource->withOperations($operations->sort());
            $resourceMetadataCollection[$i] = $resource;
        }

        return $resourceMetadataCollection;
    }

    private function generateUriTemplate(Operation $operation): string
    {
        $uriTemplate = $operation->getRoutePrefix() ?: '';
        $uriTemplate = sprintf('%s/%s', $uriTemplate, $this->pathSegmentNameGenerator->getSegmentName($operation->getShortName()));

        if ($parameters = array_keys($operation->getIdentifiers())) {
            if ($operation->getCompositeIdentifier()) {
                $uriTemplate .= sprintf('/{%s}', $parameters[0]);
            } else {
                foreach ($parameters as $parameterName) {
                    $uriTemplate .= sprintf('/{%s}', $parameterName);
                }
            }
        }

        return sprintf('%s.{_format}', $uriTemplate);
    }
}
