<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Swagger\Util;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class SwaggerDefinitions
{
    /**
     * @var \ArrayObject
     */
    private $definitions;

    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $typeResolver;
    private $nameConverter;

    /**
     * RouteDocumentationExtractor constructor.
     *
     * @param ResourceMetadataFactoryInterface       $resourceMetadataFactory
     * @param PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory
     * @param PropertyMetadataFactoryInterface       $propertyMetadataFactory
     * @param SwaggerTypeResolver                    $typeResolver
     * @param NameConverterInterface                 $nameConverter
     */
    public function __construct(
        ResourceMetadataFactoryInterface $resourceMetadataFactory,
        PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        PropertyMetadataFactoryInterface $propertyMetadataFactory,
        SwaggerTypeResolver $typeResolver,
        NameConverterInterface $nameConverter = null
    ) {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->nameConverter = $nameConverter;
        $this->typeResolver = $typeResolver;
        $this->definitions = new \ArrayObject();
    }

    /**
     * @param array $operationData
     * @param bool  $isDenormalization
     *
     * @return string
     */
    public function get(array $operationData, bool $isDenormalization = true)
    {
        if (!SwaggerOperationDataGuard::check($operationData)) {
            throw  new InvalidArgumentException('invalid $operationData argument ');
        }
        $resourceMetadata = $this->resourceMetadataFactory->create($operationData['resourceClass']);
        $serializerContext = $this->getSerializerContext($operationData, $resourceMetadata, $isDenormalization);
        $definitionKey = $this->getDefinitionKey($serializerContext, $resourceMetadata);

        if (!array_key_exists($definitionKey, $this->definitions)) {
            $definitions = $this->getDefinitionSchema($operationData, $resourceMetadata, $serializerContext, $definitionKey);
            if (array_key_exists('properties', $definitions)) {
                $this->definitions[$definitionKey] = $definitions;
            }
        }

        return array_key_exists($definitionKey, $this->definitions) ? $definitionKey : null;
    }

    /**
     * @return \ArrayObject
     */
    public function getDefinitions(): \ArrayObject
    {
        return $this->definitions;
    }

    /**
     * Gets a definition Schema Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#schemaObject
     *
     * @param array            $operationData
     * @param ResourceMetadata $resourceMetadata
     * @param array|null       $serializerContext
     *
     * @return \ArrayObject
     */
    private function getDefinitionSchema(array $operationData, ResourceMetadata $resourceMetadata, array $serializerContext = null, string $definitionKey): \ArrayObject
    {
        $definitionSchema = new \ArrayObject(['type' => 'object']);

        if (null !== $description = $resourceMetadata->getDescription()) {
            $definitionSchema['description'] = $description;
        }

        if (null !== $iri = $resourceMetadata->getIri()) {
            $definitionSchema['externalDocs'] = ['url' => $iri];
        }

        $options = isset($serializerContext['groups']) ? ['serializer_groups' => $serializerContext['groups']] : [];
        foreach ($this->propertyNameCollectionFactory->create($operationData['resourceClass'], $options) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($operationData['resourceClass'], $propertyName);
            $normalizedPropertyName = $this->nameConverter ? $this->nameConverter->normalize($propertyName) : $propertyName;

            if ($propertyMetadata->isRequired()) {
                $definitionSchema['required'][] = $normalizedPropertyName;
            }

            $definitionSchema['properties'][$normalizedPropertyName] = $this->getPropertySchema($propertyMetadata, $definitionKey);
        }

        return $definitionSchema;
    }

    /**
     * Gets a property Schema Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#schemaObject
     *
     * @param PropertyMetadata $propertyMetadata
     *
     * @return \ArrayObject
     */
    private function getPropertySchema(PropertyMetadata $propertyMetadata, string $definitionKey): \ArrayObject
    {
        $propertySchema = new \ArrayObject();

        if (false === $propertyMetadata->isWritable()) {
            $propertySchema['readOnly'] = true;
        }

        if (null !== $description = $propertyMetadata->getDescription()) {
            $propertySchema['description'] = $description;
        }

        if (null === $type = $propertyMetadata->getType()) {
            return $propertySchema;
        }

        $isCollection = $type->isCollection();
        if (null === $valueType = $isCollection ? $type->getCollectionValueType() : $type) {
            $builtinType = 'string';
            $className = null;
        } else {
            $builtinType = $valueType->getBuiltinType();
            $className = $valueType->getClassName();
        }

        $valueSchema = $this->typeResolver->resolve(
            $builtinType, $isCollection, $className, $propertyMetadata->isReadableLink(), $definitionKey
        );

        return new \ArrayObject((array) $propertySchema + $valueSchema);
    }

    /**
     * @param array            $operationData
     * @param ResourceMetadata $resourceMetadata
     * @param bool             $isDenormalization
     *
     * @return mixed
     */
    private function getSerializerContext(
        array $operationData,
        ResourceMetadata $resourceMetadata,
        bool $isDenormalization
    ) {
        $contextKey = $isDenormalization ? 'denormalization_context' : 'normalization_context';

        if ($operationData['isCollection']) {
            return $resourceMetadata->getCollectionOperationAttribute(
                $operationData['operationName'],
                $contextKey,
                null,
                true);
        } else {
            return $resourceMetadata->getItemOperationAttribute(
                $operationData['operationName'],
                $contextKey,
                null,
                true);
        }
    }

    /**
     * @param $serializerContext
     * @param ResourceMetadata $resourceMetadata
     *
     * @return string
     */
    private function getDefinitionKey($serializerContext, ResourceMetadata $resourceMetadata): string
    {
        if (isset($serializerContext['groups'])) {
            $definitionKey = sprintf('%s_%s', $resourceMetadata->getShortName(), md5(serialize($serializerContext['groups'])));
        } else {
            $definitionKey = $resourceMetadata->getShortName();
        }

        return $definitionKey;
    }
}
