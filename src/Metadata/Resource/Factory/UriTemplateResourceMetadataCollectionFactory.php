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
use ApiPlatform\Metadata\Operation\PathSegmentNameGeneratorInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Symfony\Component\Routing\Route;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class UriTemplateResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use OperationDefaultsTrait;

    private $triggerLegacyFormatOnce = [];

    public function __construct(private readonly LinkFactoryInterface $linkFactory, private readonly PathSegmentNameGeneratorInterface $pathSegmentNameGenerator, private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
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

                if (
                    $operation->getUriTemplate()
                    && !($operation->getExtraProperties()['generated_operation'] ?? false)
                ) {
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

                    $operations->add($operation->getName(), $operation);
                    continue;
                }

                $operation = $operation->withUriTemplate($this->generateUriTemplate($operation));

                if (!$operation->getName()) {
                    $operation = $operation->withName($this->getDefaultOperationName($operation, $resourceClass));
                }

                $operations->add($operation->getName(), $operation);
            }

            $resource = $resource->withOperations($operations);
            $resourceMetadataCollection[$i] = $resource;
        }

        return $resourceMetadataCollection;
    }

    private function generateUriTemplate(HttpOperation $operation): string
    {
        $uriTemplate = $operation->getUriTemplate() ?? sprintf('/%s', $this->pathSegmentNameGenerator->getSegmentName($operation->getShortName()));
        $uriVariables = $operation->getUriVariables() ?? [];
        $legacyFormat = null;

        if (str_ends_with($uriTemplate, '{._format}') || ($legacyFormat = str_ends_with($uriTemplate, '.{_format}'))) {
            $uriTemplate = substr($uriTemplate, 0, -10);
        }

        if ($legacyFormat && ($this->triggerLegacyFormatOnce[$operation->getClass()] ?? true)) {
            $this->triggerLegacyFormatOnce[$operation->getClass()] = false;
            trigger_deprecation('api-platform/core', '3.0', sprintf('The special Symfony parameter ".{_format}" in your URI Template is deprecated, use an RFC6570 variable "{._format}" on the class "%s" instead. We will only use the RFC6570 compatible variable in 4.0.', $operation->getClass()));
        }

        if ($parameters = array_keys($uriVariables)) {
            foreach ($parameters as $parameterName) {
                $part = sprintf('/{%s}', $parameterName);
                if (!str_contains($uriTemplate, $part)) {
                    $uriTemplate .= sprintf('/{%s}', $parameterName);
                }
            }
        }

        return sprintf('%s%s', $uriTemplate, $legacyFormat ? '.{_format}' : '{._format}');
    }

    private function configureUriVariables(ApiResource|HttpOperation $operation): ApiResource|HttpOperation
    {
        // We will generate the collection route, don't initialize variables here
        if ($operation instanceof HttpOperation && (
            [] === $operation->getUriVariables()
            || (
                $operation instanceof CollectionOperationInterface
                && null === $operation->getUriTemplate()
            )
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
            if ($operation instanceof HttpOperation && 'POST' === $operation->getMethod()) {
                return $operation->withUriVariables([]);
            }

            return $operation;
        }

        foreach ($uriVariables = $operation->getUriVariables() as $parameterName => $l) {
            $link = null === $l->getFromClass() ? $l->withFromClass($operation->getClass()) : $l;
            $uriVariables[$parameterName] = $this->linkFactory->completeLink($link);
        }
        $operation = $operation->withUriVariables($uriVariables);

        if (str_ends_with($uriTemplate, '{._format}') || str_ends_with($uriTemplate, '.{_format}')) {
            $uriTemplate = substr($uriTemplate, 0, -10);
        }

        // TODO: move this to the Symfony bridge
        if (class_exists(Route::class)) {
            $route = (new Route($uriTemplate))->compile();
            $variables = $route->getPathVariables();

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

                    $newUriVariables[$variable] = (new Link())
                        ->withFromClass($operation->getClass())
                        ->withIdentifiers([property_exists($operation->getClass(), $variable) ? $variable : 'id'])
                        ->withParameterName($variable);
                }

                return $operation->withUriVariables($newUriVariables);
            }

            // When an operation is generated we need to find properties matching it's uri variables
            if (!($operation->getExtraProperties()['generated_operation'] ?? false) || !$this->linkFactory instanceof PropertyLinkFactoryInterface) {
                return $operation;
            }

            $diff = array_diff($variables, array_keys($uriVariables));
            if (0 === \count($diff)) {
                return $operation;
            }

            // We generated this operation but there're some missing identifiers
            $uriVariables = 'POST' === $operation->getMethod() || $operation instanceof CollectionOperationInterface ? [] : $operation->getUriVariables();

            foreach ($diff as $key) {
                $uriVariables[$key] = $this->linkFactory->createLinkFromProperty($operation, $key);
            }

            return $operation->withUriVariables($uriVariables);
        }

        return $operation;
    }

    private function normalizeUriVariables(ApiResource|HttpOperation $operation): ApiResource|HttpOperation
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
