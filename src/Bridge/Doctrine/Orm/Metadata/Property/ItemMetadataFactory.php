<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Bridge\Doctrine\Orm\Metadata\Property;

use ApiPlatform\Builder\Metadata\Property\Factory\ItemMetadataFactoryInterface;
use ApiPlatform\Builder\Metadata\Property\ItemMetadata;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Use Doctrine metadata to populate the identifier property.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemMetadataFactory implements ItemMetadataFactoryInterface
{
    private $decorated;
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry, ItemMetadataFactoryInterface $decorated)
    {
        $this->managerRegistry = $managerRegistry;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []) : ItemMetadata
    {
        $itemMetadata = $this->decorated->create($resourceClass, $property, $options);

        if (null !== $itemMetadata->isIdentifier()) {
            return $itemMetadata;
        }

        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (!$manager) {
            return $itemMetadata;
        }

        $doctrineClassMetadata = $manager->getClassMetadata($resourceClass);
        if (!$doctrineClassMetadata) {
            return $itemMetadata;
        }

        $identifiers = $doctrineClassMetadata->getIdentifier();
        foreach ($identifiers as $identifier) {
            if ($identifier === $property) {
                $itemMetadata = $itemMetadata->withIdentifier(true);
                $itemMetadata = $itemMetadata->withReadable(false);
                $itemMetadata = $itemMetadata->withWritable($doctrineClassMetadata->isIdentifierNatural());

                break;
            }
        }

        if (null === $itemMetadata->isIdentifier()) {
            $itemMetadata = $itemMetadata->withIdentifier(false);
        }

        return $itemMetadata;
    }
}
