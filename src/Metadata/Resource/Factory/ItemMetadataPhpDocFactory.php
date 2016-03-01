<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\ItemMetadata;
use phpDocumentor\Reflection\ClassReflector;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\FileReflector;
use phpDocumentor\Reflection\Types\ContextFactory;

/**
 * Extracts descriptions from PHPDoc.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemMetadataPhpDocFactory implements ItemMetadataFactoryInterface
{
    private $decorated;
    private $docBlockFactory;
    private $contextFactory;

    public function __construct(ItemMetadataFactoryInterface $decorated, DocBlockFactoryInterface $docBlockFactory = null)
    {
        $this->decorated = $decorated;
        $this->docBlockFactory = $docBlockFactory ?: DocBlockFactory::createInstance();
        $this->contextFactory = new ContextFactory();
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
        if ($docBlock = $this->docBlockFactory->create($reflectionClass, $this->contextFactory->createFromReflector($reflectionClass))) {
            $itemMetadata = $itemMetadata->withDescription($docBlock->getSummary());
        }

        return $itemMetadata;
    }
}
