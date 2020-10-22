<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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

        foreach ($resourceMetadata as $path => $operationCollectionMetadata) {
            if (null !== $operationCollectionMetadata->getShortName()) {
                continue;
            }

            if (false !== $pos = strrpos($resourceClass, '\\')) {
                $resourceMetadata[$path] = $operationCollectionMetadata->withShortName(substr($resourceClass, $pos + 1));

                continue;
            }

            $resourceMetadata[$path] = $operationCollectionMetadata->withShortName($resourceClass);
        }

        return $resourceMetadata;
    }
}
