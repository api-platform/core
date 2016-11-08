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

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * Guesses the short name from the class name if not already set.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ShortNameResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $decorated;

    public function __construct(ResourceMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = $this->decorated->create($resourceClass);

        if (null !== $resourceMetadata->getShortName()) {
            return $resourceMetadata;
        }

        return $resourceMetadata->withShortName(substr($resourceClass, strrpos($resourceClass, '\\') + 1));
    }
}
