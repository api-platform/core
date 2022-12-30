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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Operation\PathSegmentNameGeneratorInterface;
use Symfony\Component\Routing\Route;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class UriTemplateResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private $linkFactory;
    private $pathSegmentNameGenerator;
    private $decorated;

    public function __construct(LinkFactoryInterface $linkFactory, PathSegmentNameGeneratorInterface $pathSegmentNameGenerator, ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
        $this->linkFactory = $linkFactory;
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
            /** @var ApiResource $resource */
            $resource = $this->configureUriVariables($resource);
            if ($resource->getUriTemplate()) {
                $resourceMetadataCollection[$i] = $resource->withExtraProperties($resource->getExtraProperties() + ['user_defined_uri_template' => true]);
            }

            $operations = new Operations();
            foreach ($resource->getOperations() ?? new Operations() as $key => $operation) {
                /** @var HttpOperation */
                $operation = $this->configureUriVariables($operation);

                if ($operation->getUriTemplate()) {
                    $operation = $operation->withExtraProperties($operation->getExtraProperties() + ['user_defined_uri_template' => true]);
                    if (!$operation->getName()) {
                        $operation = $operation->withName($key);
                    }

                    $operations->add($key, $operation);
                    continue;
                }

                if ($routeName = $operation->getRouteName()) {
                    if (!$operation->getName()) {
                        $operation = $operation->withName($routeName);
                    }

                    $operations->add($routeName, $operation);
                    continue;
                }

                $operation = $operation->withUriTemplate($this->generateUriTemplate($operation));
                $operationName = $operation->getName() ?: sprintf('_api_%s_%s%s', $operation->getUriTemplate(), strtolower($operation->getMethod() ?? HttpOperation::METHOD_GET), $operation instanceof CollectionOperationInterface ? '_collection' : '');
                if (!$operation->getName()) {
                    $operation = $operation->withName($operationName);
                }

                $operations->add($operationName, $operation);
            }

            $resource = $resource->withOperations($operations->sort());
            $resourceMetadataCollection[$i] = $resource;
        }

        return $resourceMetadataCollection;
    }

    private function generateUriTemplate(HttpOperation $operation): string
    {
        $uriTemplate = sprintf('/%s', $this->pathSegmentNameGenerator->getSegmentName($operation->getShortName()));
        $uriVariables = $operation->getUriVariables() ?? [];

        if ($parameters = array_keys($uriVariables)) {
            if (($operation->getExtraProperties()['is_legacy_resource_metadata'] ?? false) && 1 < \count($uriVariables[$parameters[0]]->getIdentifiers() ?? [])) {
                $parameters[0] = 'id';
            }

            foreach ($parameters as $parameterName) {
                $uriTemplate .= sprintf('/{%s}', $parameterName);
            }
        }

        return sprintf('%s.{_format}', $uriTemplate);
    }

    /**
     * @param ApiResource|HttpOperation $operation
     *
     * @return ApiResource|HttpOperation
     */
    private function configureUriVariables($operation)
    {
        // We will generate the collection route, don't initialize variables here
        if ($operation instanceof HttpOperation && (
            [] === $operation->getUriVariables() ||
            ($operation instanceof CollectionOperationInterface && null === $operation->getUriTemplate())
        )) {
            if (null === $operation->getUriVariables()) {
                return $operation;
            }

            return $this->normalizeUriVariables($operation);
        }

        $hasUserConfiguredUriVariables = !($operation->getExtraProperties()['is_legacy_resource_metadata'] ?? false);
        if (!$operation->getUriVariables()) {
            $hasUserConfiguredUriVariables = false;
            $operation = $operation->withUriVariables($this->transformLinksToUriVariables($this->linkFactory->createLinksFromIdentifiers($operation)));
        }

        $operation = $this->normalizeUriVariables($operation);

        if (!($uriTemplate = $operation->getUriTemplate())) {
            if ($operation instanceof HttpOperation && HttpOperation::METHOD_POST === $operation->getMethod()) {
                return $operation->withUriVariables([]);
            }

            return $operation;
        }

        foreach ($uriVariables = $operation->getUriVariables() as $parameterName => $link) {
            $uriVariables[$parameterName] = $this->linkFactory->completeLink($link);
        }
        $operation = $operation->withUriVariables($uriVariables);

        $route = (new Route($uriTemplate))->compile();
        $variables = array_filter($route->getPathVariables(), function ($v) {
            return '_format' !== $v;
        });

        if (\count($variables) !== \count($uriVariables)) {
            if ($hasUserConfiguredUriVariables) {
                return $operation;
            }

            $newUriVariables = [];
            foreach ($variables as $variable) {
                if (isset($uriVariables[$variable])) {
                    $newUriVariables[$variable] = $uriVariables[$variable];
                    continue;
                }

                $newUriVariables[$variable] = (new Link())->withFromClass($operation->getClass())->withIdentifiers(['id'])->withParameterName($variable);
            }

            return $operation->withUriVariables($newUriVariables);
        }

        return $operation;
    }

    /**
     * @param ApiResource|HttpOperation $operation
     *
     * @return ApiResource|HttpOperation
     */
    private function normalizeUriVariables($operation)
    {
        $uriVariables = (array) ($operation->getUriVariables() ?? []);

        $normalizedUriVariables = [];
        $resourceClass = $operation->getClass();

        foreach ($uriVariables as $parameterName => $uriVariable) {
            $normalizedParameterName = $parameterName;
            $normalizedUriVariable = $uriVariable;

            if (\is_int($normalizedParameterName)) {
                $normalizedParameterName = $normalizedUriVariable;
            }
            if (\is_string($normalizedUriVariable)) {
                $normalizedUriVariable = (new Link())->withIdentifiers([$normalizedUriVariable])->withFromClass($resourceClass);
            }
            if (\is_array($normalizedUriVariable)) {
                if (!isset($normalizedUriVariable['from_class']) && !isset($normalizedUriVariable['expanded_value'])) {
                    if (2 !== \count($normalizedUriVariable)) {
                        throw new \LogicException("The uriVariables shortcut syntax needs to be the tuple: 'uriVariable' => [fromClass, fromProperty]");
                    }
                    $normalizedUriVariable = (new Link())->withFromProperty($normalizedUriVariable[1])->withFromClass($normalizedUriVariable[0]);
                } else {
                    $normalizedUriVariable = new Link($normalizedParameterName, $normalizedUriVariable['from_property'] ?? null, $normalizedUriVariable['to_property'] ?? null, $normalizedUriVariable['from_class'] ?? null, $normalizedUriVariable['to_class'] ?? null, $normalizedUriVariable['identifiers'] ?? null, $normalizedUriVariable['composite_identifier'] ?? null, $normalizedUriVariable['expanded_value'] ?? null);
                }
            }

            $normalizedUriVariable = $normalizedUriVariable->withParameterName($normalizedParameterName);
            $normalizedUriVariables[$normalizedParameterName] = $normalizedUriVariable;
        }

        return $operation->withUriVariables($normalizedUriVariables);
    }

    /**
     * @param Link[] $links
     *
     * @return array<string, Link>
     */
    private function transformLinksToUriVariables(array $links): array
    {
        $uriVariables = [];

        foreach ($links as $link) {
            $uriVariables[$link->getParameterName()] = $link;
        }

        return $uriVariables;
    }
}
