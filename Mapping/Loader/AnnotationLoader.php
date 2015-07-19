<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Mapping\Loader;

use Doctrine\Common\Annotations\Reader;
use Dunglas\ApiBundle\Mapping\ClassMetadataInterface;
use Dunglas\ApiBundle\Util\ReflectionTrait;

/**
 * Annotation loader.
 *
 * Attributes must be loaded first.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AnnotationLoader implements LoaderInterface
{
    use ReflectionTrait;

    /**
     * @var string
     */
    const IRI_ANNOTATION_NAME = 'Dunglas\ApiBundle\Annotation\Iri';

    /**
     * @var Reader
     */
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(
        ClassMetadataInterface $classMetadata,
        array $normalizationGroups = null,
        array $denormalizationGroups = null,
        array $validationGroups = null
    ) {
        $reflectionClass = $classMetadata->getReflectionClass();
        if ($iri = $this->reader->getClassAnnotation($reflectionClass, self::IRI_ANNOTATION_NAME)) {
            $classMetadata = $classMetadata->withIri($iri->value);
        }

        foreach ($classMetadata->getAttributesMetadata() as $attributeName => $attributeMetadata) {
            if ($reflectionProperty = $this->getReflectionProperty($reflectionClass, $attributeName)) {
                if ($iri = $this->reader->getPropertyAnnotation($reflectionProperty, self::IRI_ANNOTATION_NAME)) {
                    $classMetadata = $classMetadata->withAttributeMetadata($attributeName, $attributeMetadata->withIri($iri->value));
                }
            }
        }

        return $classMetadata;
    }
}
