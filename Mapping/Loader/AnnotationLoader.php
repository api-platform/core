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
use Dunglas\ApiBundle\Mapping\ClassMetadata;
use Dunglas\ApiBundle\Util\ReflectionTrait;
use Symfony\Component\Validator\Constraint;

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
     * @var string
     */
    const CONSTRAINT_ANNOTATION_NAME = 'Symfony\Component\Validator\Constraints';

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
        ClassMetadata $classMetadata,
        array $normalizationGroups = null,
        array $denormalizationGroups = null,
        array $validationGroups = null
    ) {
        $reflectionClass = $classMetadata->getReflectionClass();
        if ($iri = $this->reader->getClassAnnotation($reflectionClass, self::IRI_ANNOTATION_NAME)) {
            $classMetadata->setIri($iri->value);
        }

        foreach ($classMetadata->getAttributes() as $attributeMetadata) {
            $attributeName = $attributeMetadata->getName();

            if ($reflectionProperty = $this->getReflectionProperty($reflectionClass, $attributeName)) {
                if ($iri = $this->reader->getPropertyAnnotation($reflectionProperty, self::IRI_ANNOTATION_NAME)) {
                    $attributeMetadata->setIri($iri->value);
                }
                if ($annotations = $this->reader->getPropertyAnnotations($reflectionProperty)) {
                    $constraints = [];
                    foreach ($annotations as $annotation) {
                        if ($annotation instanceof Constraint) {
                            $annotationOptions = (array) $annotation;

                            // Cleanup the options by removing useless data (remove payload and messages)
                            unset($annotationOptions['payload']);
                            unset($annotationOptions['message']);
                            foreach ($annotationOptions as $key => $annotationOption) {
                                if (substr(strtolower($key), -7) == 'message') {
                                    unset($annotationOptions[$key]);
                                }
                            }

                            $constraints[] = [
                                'name' => get_class($annotation),
                                'options' => $annotationOptions,
                            ];
                        }
                    }
                    $attributeMetadata->setSymfonyConstraints($constraints);
                }
            }
        }

        return true;
    }
}
