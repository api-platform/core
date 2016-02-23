<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\ItemMetadata;
use phpDocumentor\Reflection\ClassReflector;
use phpDocumentor\Reflection\FileReflector;

/**
 * Extracts descriptions from PHPDoc.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemMetadataPhpDocFactory implements ItemMetadataFactoryInterface
{
    /**
     * @var FileReflector[]
     */
    private static $fileReflectors = [];

    /**
     * @var ClassReflector[]
     */
    private static $classReflectors = [];

    private $decorated;

    public function __construct(ItemMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass) : ItemMetadata
    {
        $itemMetadata = $this->decorated->create($resourceClass);

        if (null !== $itemMetadata->getDescription()) {
            return $itemMetadata;
        }

        $reflectionClass = new \ReflectionClass($resourceClass);

        if (
            ($classReflector = $this->getClassReflector($reflectionClass)) &&
            $docBlock = $classReflector->getDocBlock()
        ) {
            $itemMetadata = $itemMetadata->withDescription($docBlock->getShortDescription());
        }

        return $itemMetadata;
    }

    /**
     * Gets the ClassReflector associated with this class.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return ClassReflector|null
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
