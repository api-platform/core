<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Nelmio\Parser;

use Dunglas\ApiBundle\Api\ResourceCollectionInterface;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Mapping\AttributeMetadataInterface;
use Dunglas\ApiBundle\Mapping\Factory\ClassMetadataFactoryInterface;
use Nelmio\ApiDocBundle\DataTypes;
use Nelmio\ApiDocBundle\Parser\ParserInterface;
use PropertyInfo\Type;

/**
 * Extract input and output information for the NelmioApiDocBundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiParser implements ParserInterface
{
    const IN_PREFIX = 'dunglas_api_in';
    const OUT_PREFIX = 'dunglas_api_out';
    const IRI = 'IRI';

    private static $typeMap = array(
        'int' => DataTypes::INTEGER,
        'bool' => DataTypes::BOOLEAN,
        'string' => DataTypes::STRING,
        'float' => DataTypes::FLOAT,
    );

    /**
     * @var ResourceCollectionInterface
     */
    private $resourceCollection;
    /**
     * @var ClassMetadataFactoryInterface
     */
    private $classMetadataFactory;

    public function __construct(
        ResourceCollectionInterface $resourceCollection,
        ClassMetadataFactoryInterface $classMetadataFactory
    ) {
        $this->resourceCollection = $resourceCollection;
        $this->classMetadataFactory = $classMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $item)
    {
        $data = explode(':', $item['class'], 2);
        if (isset($data[1])) {
            return null !== $this->resourceCollection->getResourceForEntity($data[1]);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(array $item)
    {
        list($io, $entityClass) = explode(':', $item['class'], 2);
        $resource = $this->resourceCollection->getResourceForEntity($entityClass);

        return $this->parseClass($resource, $entityClass, $io);
    }

    /**
     * Parses a class.
     *
     * @param ResourceInterface $resource
     * @param string            $entityClass
     * @param string            $io
     *
     * @return array
     */
    private function parseClass(ResourceInterface $resource, $entityClass, $io)
    {
        $classMetadata = $this->classMetadataFactory->getMetadataFor(
            $entityClass,
            $resource->getNormalizationGroups(),
            $resource->getDenormalizationGroups(),
            $resource->getValidationGroups()
        );

        $data = array();
        /** @var AttributeMetadataInterface $attributeMetadata */
        foreach ($classMetadata->getAttributesMetadata() as $name => $attributeMetadata) {
            if (
                ($attributeMetadata->isReadable() && self::OUT_PREFIX === $io) ||
                ($attributeMetadata->isWritable() && self::IN_PREFIX === $io)
            ) {
                $data[$name] = $this->parseAttribute($resource, $attributeMetadata, $io);
            }
        }

        return $data;
    }

    /**
     * Parses an attribute.
     *
     * @param ResourceInterface          $resource
     * @param AttributeMetadataInterface $attributeMetadata
     * @param string                     $io
     * @param Type|null                  $type
     *
     * @return array
     */
    private function parseAttribute(ResourceInterface $resource, AttributeMetadataInterface $attributeMetadata, $io, Type $type = null)
    {
        $data = array(
            'dataType' => null,
            'required' => $attributeMetadata->isRequired(),
            'description' => $attributeMetadata->getDescription(),
            'readonly' => !$attributeMetadata->isWritable(),
        );

        if (null == $type) {
            if (null === $attributeMetadata->getType()) {
                // Default to string
                $data['dataType'] = DataTypes::STRING;

                return $data;
            }

            $type = $attributeMetadata->getType();
        }

        if ($type->isCollection()) {
            $data['actualType'] = DataTypes::COLLECTION;

            if ($collectionType = $type->getCollectionType()) {
                $subAttribute = $this->parseAttribute($resource, $attributeMetadata, $io, $collectionType);
                if (self::IRI === $subAttribute['dataType']) {
                    $data['dataType'] = 'array of IRIs';
                    $data['subType'] = DataTypes::STRING;

                    return $data;
                }

                $data['subType'] = $subAttribute['subType'];
                $data['children'] = $subAttribute['children'];
            }

            return $data;
        }

        $phpType = $type->getType();
        if ('object' === $phpType) {
            $class = $type->getClass();

            if ('DateTime' === $class) {
                $data['dataType'] = DataTypes::DATETIME;
                $data['format'] = sprintf('{DateTime %s}', \DateTime::ATOM);

                return $data;
            }

            if (
                (self::OUT_PREFIX === $io && $attributeMetadata->isNormalizationLink()) ||
                (self::IN_PREFIX === $io && $attributeMetadata->isDenormalizationLink())
            ) {
                $data['dataType'] = self::IRI;
                $data['actualType'] = DataTypes::STRING;

                return $data;
            }

            $data['actualType'] = DataTypes::MODEL;
            $data['subType'] = $class;
            $data['children'] = $this->parseClass($resource, $class, $io);

            return $data;
        }

        $data['dataType'] = isset(self::$typeMap[$type->getType()]) ? self::$typeMap[$type->getType()] : DataTypes::STRING;

        return $data;
    }
}
