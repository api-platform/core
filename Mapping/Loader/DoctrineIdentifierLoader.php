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

use Doctrine\ORM\EntityManager;
use Dunglas\ApiBundle\Mapping\ClassMetadata;

/**
 * Doctrine identifier loader.
 *
 * @author Mikaël Labrut <labrut@gmail.com>
 */
class DoctrineIdentifierLoader implements LoaderInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
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
        $doctrineClassMetaData = $this->entityManager->getClassMetadata($classMetadata->getReflectionClass()->getName());
        $identifiers = $doctrineClassMetaData->getIdentifier();

        if (1 === count($identifiers)) {
            $identifierName = $identifiers[0];

            foreach ($classMetadata->getAttributes() as $attribute) {
                if ($attribute->getName() === $identifierName) {
                    $attribute->setIdentifier(true);
                    break;
                }
            }
        }

        return true;
    }
}
