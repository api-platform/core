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
    public function create(string $resourceClass) : ItemMetadata
    {
        $itemMetadata = $this->decorated->create($resourceClass);

        if (null !== $itemMetadata->getShortName()) {
            return $itemMetadata;
        }

        return $itemMetadata->withShortName(substr($resourceClass, strrpos($resourceClass, '\\') + 1));
    }
}
