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
use Dunglas\ApiBundle\Mapping\ClassMetadata;

/**
 * Doctrine identifier loader.
 *
 * @author Mikaël Labrut <labrut@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DoctrineIdentifierLoader implements LoaderInterface
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
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
        $className = $classMetadata->getReflectionClass()->name;

        $manager = $this->managerRegistry->getManagerForClass($className);
        if (!$manager) {
            return true;
        }

        $doctrineClassMetaData = $manager->getClassMetadata($className);
        if (!$doctrineClassMetaData) {
            return true;
        }

        $identifiers = $doctrineClassMetaData->getIdentifier();
        if (1 !== count($identifiers)) {
            return true;
        }

        $identifierName = $identifiers[0];
        foreach ($classMetadata->getAttributes() as $attribute) {
            if ($attribute->getName() === $identifierName) {
                $attribute->setIdentifier(true);

                return true;
            }
        }

        return true;
    }
}
