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

use Doctrine\Common\Persistence\ManagerRegistry;
use Dunglas\ApiBundle\Mapping\Factory\AttributeMetadataFactoryInterface;
use Dunglas\ApiBundle\Mapping\ClassMetadataInterface;

/**
 * Doctrine identifier loader.
 *
 * @author Mikaël Labrut <labrut@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DoctrineIdentifierLoader implements LoaderInterface
{
    /**
     * @var AttributeMetadataFactoryInterface
     */
    private $attributeMetadataFactory;
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    public function __construct(AttributeMetadataFactoryInterface $attributeMetadataFactory, ManagerRegistry $managerRegistry)
    {
        $this->attributeMetadataFactory = $attributeMetadataFactory;
        $this->managerRegistry = $managerRegistry;
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
        $className = $classMetadata->getReflectionClass()->name;

        $manager = $this->managerRegistry->getManagerForClass($className);
        if (!$manager) {
            return $classMetadata;
        }

        $doctrineClassMetaData = $manager->getClassMetadata($className);
        if (!$doctrineClassMetaData) {
            return $classMetadata;
        }

        $identifiers = $doctrineClassMetaData->getIdentifier();
        if (1 !== count($identifiers)) {
            return $classMetadata;
        }

        $identifierName = $identifiers[0];
        if (!$classMetadata->hasAttributeMetadata($identifierName)) {
            $attributeMetadata = $this->attributeMetadataFactory->getAttributeMetadataFor(
                $classMetadata, $identifierName, $normalizationGroups, $denormalizationGroups
            );
            $classMetadata = $classMetadata->withAttributeMetadata($identifierName, $attributeMetadata);
        }

        $classMetadata = $classMetadata->withIdentifierName($identifierName);

        return $classMetadata;
    }
}
