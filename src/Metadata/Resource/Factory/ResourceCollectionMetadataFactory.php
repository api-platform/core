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

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceToResourceMetadataTrait;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;

/**
 * BC layer with the < 3.0 ResourceMetadata system.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ResourceCollectionMetadataFactory implements ResourceMetadataFactoryInterface
{
    use ResourceToResourceMetadataTrait;
    private $decorated;
    private $resourceCollectionMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $decorated, ResourceCollectionMetadataFactoryInterface $resourceCollectionMetadataFactory)
    {
        $this->decorated = $decorated;
        $this->resourceCollectionMetadataFactory = $resourceCollectionMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        try {
            return $this->decorated->create($resourceClass);
        } catch (ResourceClassNotFoundException $e) {
            $resourceCollection = $this->resourceCollectionMetadataFactory->create($resourceClass);
            if (!isset($resourceCollection[0])) {
                throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
            }

            @trigger_error(sprintf('Using a %s for a #[Resource] is deprecated since 2.7 and will not be possible in 3.0. Use %s instead.', ResourceMetadataFactoryInterface::class, ResourceCollectionMetadataFactoryInterface::class), \E_USER_DEPRECATED);

            return $this->transformResourceToResourceMetadata($resourceCollection[0]);
        }
    }
}
