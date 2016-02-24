<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Mapping\Loader;

use Dunglas\ApiBundle\Mapping\ClassMetadata;
use Dunglas\ApiBundle\Util\ReflectionTrait;
use phpDocumentor\Reflection\FileReflector;
use PropertyInfo\PropertyInfoInterface;

/**
 * Extracts descriptions from PHPDoc.
 *
 * Attributes must be loaded first.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PhpDocLoader implements LoaderInterface
{
    use ReflectionTrait;

    /**
     * @var FileReflector[]
     */
    private static $fileReflectors = [];
    /**
     * @var ClassReflector[]
     */
    private static $classReflectors = [];

    /**
     * @var PropertyInfoInterface
     */
    private $propertyInfo;

    public function __construct(PropertyInfoInterface $propertyInfo)
    {
        $this->propertyInfo = $propertyInfo;
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
        if (
            ($classReflector = $this->getClassReflector($classMetadata->getReflectionClass())) &&
            $docBlock = $classReflector->getDocBlock()
        ) {
            $classMetadata->setDescription($docBlock->getShortDescription());
        }

        foreach ($classMetadata->getAttributes() as $attributeMetadata) {
            if ($reflectionProperty = $this->getReflectionProperty(
                $classMetadata->getReflectionClass(), $attributeMetadata->getName())
            ) {
                $attributeMetadata->setDescription($this->propertyInfo->getShortDescription($reflectionProperty));
            }
        }

        return true;
    }

    /**
     * Gets the ClassReflector associated with this class.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return \phpDocumentor\Reflection\ClassReflector|null
     */
    private function getClassReflector(\ReflectionClass $reflectionClass)
    {
        $className = $reflectionClass->name;

        if (isset(self::$classReflectors[$className])) {
            return self::$classReflectors[$className];
        }

        if (!($fileName = $reflectionClass->getFileName())) {
            return;
        }

        if (isset(self::$fileReflectors[$fileName])) {
            $fileReflector = self::$fileReflectors[$fileName];
        } else {
            $fileReflector = self::$fileReflectors[$fileName] = new FileReflector($fileName);
            $fileReflector->process();
        }

        foreach ($fileReflector->getClasses() as $classReflector) {
            $className = $classReflector->getName();
            if ('\\' === $className[0]) {
                $className = substr($className, 1);
            }

            if ($className === $reflectionClass->name) {
                return self::$classReflectors[$className] = $classReflector;
            }
        }
    }
}
