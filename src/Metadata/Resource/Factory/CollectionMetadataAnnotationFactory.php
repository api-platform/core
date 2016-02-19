<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Metadata\Resource\Factory;

use ApiPlatform\Builder\Annotation\Resource;
use ApiPlatform\Builder\Metadata\Resource\CollectionMetadata;
use Doctrine\Common\Annotations\Reader;

/**
 * Creates a resource collection metadata from {@see Resource} annotations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class CollectionMetadataAnnotationFactory implements CollectionMetadataFactoryInterface
{
    private $reader;
    private $paths;
    private $decorated;

    /**
     * @param Reader                                  $reader
     * @param string[]                                $paths
     * @param CollectionMetadataFactoryInterface|null $decorated
     */
    public function __construct(Reader $reader, array $paths, CollectionMetadataFactoryInterface $decorated = null)
    {
        $this->reader = $reader;
        $this->paths = $paths;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create() : CollectionMetadata
    {
        $classes = [];
        $includedFiles = [];

        if ($this->decorated) {
            foreach ($this->decorated->create() as $resourceClass) {
                $classes[$resourceClass] = true;
            }
        }

        foreach ($this->paths as $path) {
            $iterator = new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                ),
                '/^.+\.php$/i',
                \RecursiveRegexIterator::GET_MATCH
            );

            foreach ($iterator as $file) {
                $sourceFile = $file[0];

                if (!preg_match('(^phar:)i', $sourceFile)) {
                    $sourceFile = realpath($sourceFile);
                }

                require_once $sourceFile;

                $includedFiles[$sourceFile] = true;
            }
        }

        $declared = get_declared_classes();
        foreach ($declared as $className) {
            $reflectionClass = new \ReflectionClass($className);
            $sourceFile = $reflectionClass->getFileName();
            if (isset($includedFiles[$sourceFile]) && $this->reader->getClassAnnotation($reflectionClass, Resource::class)) {
                $classes[$className] = true;
            }
        }

        return new CollectionMetadata(array_keys($classes));
    }
}
