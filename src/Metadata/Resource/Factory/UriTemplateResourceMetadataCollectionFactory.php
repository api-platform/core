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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\UriVariable;
use Symfony\Component\Routing\Route;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class UriTemplateResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private $pathSegmentNameGenerator;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $decorated;

    public function __construct(PathSegmentNameGeneratorInterface $pathSegmentNameGenerator, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory = null, PropertyMetadataFactoryInterface $propertyMetadataFactory = null, ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
        $this->pathSegmentNameGenerator = $pathSegmentNameGenerator;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
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
            $resource = $this->configureUriVariables($resource);

            if ($resource->getUriTemplate()) {
                $resourceMetadataCollection[$i] = $resource->withExtraProperties($resource->getExtraProperties() + ['user_defined_uri_template' => true]);
            }

            $operations = $resource->getOperations();
            foreach ($resource->getOperations() as $key => $operation) {
                $operation = $this->configureUriVariables($operation);

                if ($operation->getUriTemplate()) {
                    $operation = $operation->withExtraProperties($operation->getExtraProperties() + ['user_defined_uri_template' => true]);
                    $operations->add($key, $operation);
                    continue;
                }

                if ($routeName = $operation->getRouteName()) {
                    $operations->remove($key)->add($routeName, $operation);
                    continue;
                }

                $operation = $operation->withUriTemplate($this->generateUriTemplate($operation));
                $operationName = $operation->getName() ?: sprintf('_api_%s_%s%s', $operation->getUriTemplate(), strtolower($operation->getMethod() ?? Operation::METHOD_GET), $operation->isCollection() ? '_collection' : '');

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
     * @var ApiResource|Operation
     */
    private function configureUriVariables($operation)
    {
        // We will generate the collection route, don't initialize variables here
        if ($operation instanceof Operation && $operation->isCollection() && !$operation->getUriTemplate()) {
            return $operation;
        }

        $operation = $this->normalizeUriVariables($this->initializeUriVariables($operation));

        if (!($uriTemplate = $operation->getUriTemplate())) {
            return $operation;
        }

        foreach ($uriVariables = $operation->getUriVariables() as $parameterName => $uriVariable) {
            if (!$uriVariable->getIdentifiers()) {
                $uriVariable = $uriVariable->withIdentifiers($this->getResourceClassIdentifiers($uriVariable->getTargetClass()));
            }

            if (1 < \count($uriVariable->getIdentifiers())) {
                $uriVariable = $uriVariable->withCompositeIdentifier(true);
            }

            $uriVariables[$parameterName] = $uriVariable;
        }

        $operation = $operation->withUriVariables($uriVariables);

        $route = (new Route($uriTemplate))->compile();
        $variables = array_filter($route->getPathVariables(), function ($v) {
            return '_format' !== $v;
        });

        if (\count($variables) < \count($uriVariables)) {
            $newUriVariables = [];
            foreach ($variables as $variable) {
                if (isset($uriVariables[$variable])) {
                    $newUriVariables[$variable] = $uriVariables[$variable];
                    continue;
                }

                $newUriVariables[$variable] = (new UriVariable())->withTargetClass($operation->getClass())->withIdentifiers([$variable]);
            }

            return $operation->withUriVariables($newUriVariables);
        }

        return $operation;
    }

    /**
     * @var ApiResource|Operation
     */
    private function normalizeUriVariables($operation)
    {
        $uriVariables = $operation->getUriVariables() ?? [];
        $resourceClass = $operation->getClass();

        foreach ($uriVariables as $parameterName => $uriVariable) {
            if ($uriVariable instanceof UriVariable) {
                continue;
            }

            if (\is_int($parameterName)) {
                $uriVariables[$uriVariable] = (new UriVariable())->withIdentifiers([$uriVariable])->withTargetClass($resourceClass);
            } elseif (\is_string($uriVariable)) {
                $uriVariables[$parameterName] = (new UriVariable())->withIdentifiers([$uriVariable])->withTargetClass($resourceClass);
            } elseif (\is_array($uriVariable) && !isset($uriVariable['class'])) {
                $uriVariables[$parameterName] = (new UriVariable())->withIdentifiers($uriVariable)->withTargetClass($resourceClass);
            } elseif (\is_array($uriVariable)) {
                $uriVariables[$parameterName] = new UriVariable(null, $uriVariable['inverse_property'] ?? null, $uriVariable['property'] ?? null, $uriVariable['class'], $uriVariable['identifiers'] ?? null, $uriVariable['composite_identifier'] ?? null);
            } else {
                $uriVariables[$parameterName] = $uriVariable;
            }

            if (null !== ($hasCompositeIdentifier = $operation->getCompositeIdentifier())) {
                $uriVariables[$parameterName] = $uriVariables[$parameterName]->withCompositeIdentifier($hasCompositeIdentifier);
            }
        }

        return $this->mergeUriVariablesAttributes($operation->withUriVariables($uriVariables));
    }

    /**
     * @var ApiResource|Operation
     *
     * @return ApiResource|Operation
     */
    private function initializeUriVariables($operation)
    {
        if ($operation->getUriVariables()) {
            return $operation;
        }

        $identifiers = $this->getResourceClassIdentifiers($resourceClass = $operation->getClass());

        if (!$identifiers) {
            return $operation;
        }

        $uriVariables = [];

        if (!($operation->getCompositeIdentifier() ?? true)) {
            foreach ($identifiers as $identifier) {
                $uriVariable = (new UriVariable())->withTargetClass($resourceClass);
                $uriVariables[$identifier] = $uriVariable->withIdentifiers([$identifier])->withCompositeIdentifier(false);
            }

            return $operation->withUriVariables($uriVariables);
        }

        $uriVariable = (new UriVariable())->withTargetClass($resourceClass);
        $uriVariable = $uriVariable->withIdentifiers($identifiers);
        $parameterName = $identifiers[0];

        if (1 < \count($identifiers)) {
            $parameterName = 'id';
            $uriVariable = $uriVariable->withCompositeIdentifier(true);
        }

        return $operation->withUriVariables([$parameterName => $uriVariable]);
    }

    /**
     * Merges UriVariables with the PHP attribute UriVariable found on properties.
     *
     * @var ApiResource|Operation
     *
     * @return ApiResource|Operation
     */
    private function mergeUriVariablesAttributes($operation)
    {
        if (\PHP_VERSION_ID < 80000 || !$operation->getUriTemplate() || (!$this->propertyNameCollectionFactory && !$this->propertyMetadataFactory)) {
            return $operation;
        }

        $uriVariables = $operation->getUriVariables();

        try {
            $reflectionClass = new \ReflectionClass($resourceClass = $operation->getClass());
            foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
                $reflectionProperty = $reflectionClass->getProperty($property);

                foreach ($reflectionProperty->getAttributes(UriVariable::class) ?? [] as $attributeUriVariable) {
                    $metadata = $this->propertyMetadataFactory->create($resourceClass, $property);

                    $attributeUriVariable = $attributeUriVariable->newInstance()
                        ->withProperty($property);

                    if (!$attributeUriVariable->getTargetClass()) {
                        $attributeUriVariable = $attributeUriVariable->withTargetClass($this->getPropertyClassType($metadata->getBuiltinTypes()) ?? $resourceClass);
                    }

                    if (isset($uriVariables[$parameterName = $attributeUriVariable->getParameterName()])) {
                        $uriVariables[$parameterName] = $uriVariables[$parameterName]->withUriVariable($attributeUriVariable);
                        continue;
                    }

                    $uriVariables[$parameterName] = $attributeUriVariable;
                }
            }

            $operation = $operation->withUriVariables($uriVariables);
        } catch (\ReflectionException $e) {
        }

        return $operation;
    }

    private function getPropertyClassType(?array $types): ?string
    {
        foreach ($types ?? [] as $type) {
            if ($type->isCollection()) {
                return $this->getPropertyClassType($type->getCollectionValueTypes());
            }

            if ($class = $type->getClassName()) {
                return $class;
            }
        }

        return null;
    }

    private function getResourceClassIdentifiers(string $resourceClass): array
    {
        if (!$this->propertyNameCollectionFactory && !$this->propertyMetadataFactory) {
            return [];
        }

        $hasIdProperty = false;
        $identifiers = [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            if (!$hasIdProperty) {
                $hasIdProperty = 'id' === $property;
            }
            if ($this->propertyMetadataFactory->create($resourceClass, $property)->isIdentifier() ?? false) {
                $identifiers[] = $property;
            }
        }

        return $hasIdProperty && !$identifiers ? ['id'] : $identifiers;
    }
}
