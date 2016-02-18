<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Metadata\Resource\Factory;

use Dunglas\ApiBundle\Metadata\Resource\ItemMetadataInterface;

/**
 * Guesses the short name from the class name if not already set.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemMetadataShortNameFactory implements ItemMetadataFactoryInterface
{
    private $decorated;

    public function __construct(ItemMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass) : ItemMetadataInterface
    {
        $itemMetadata = $this->decorated->create($resourceClass);

        if (null !== $itemMetadata->getShortName()) {
            return $itemMetadata;
        }

        return $itemMetadata->withShortName(substr($resourceClass, strrpos($resourceClass, '\\') + 1));
    }
}
