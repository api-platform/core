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

final class DirectoryResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $defaults;
    private $decorated;

    public function __construct(
        array $defaults = [],
        ResourceMetadataFactoryInterface $decorated = null
    ) {
        $this->defaults = $defaults + ['attributes' => []];
        $this->decorated = $decorated;
    }

    public function create(string $resourceClass): ResourceMetadata
    {
        $parentResourceMetadata = null;
        if ($this->decorated) {
            try {
                $parentResourceMetadata = $this->decorated->create($resourceClass);
            } catch (ResourceClassNotFoundException $e) {
            }
        }
        $resource['shortName'] = (new \ReflectionClass($resourceClass))->getShortName();

        return $this->update($parentResourceMetadata ?: new ResourceMetadata(), $resource);
    }

    /**
     * Creates a new instance of metadata if the property is not already set.
     */
    private function update(ResourceMetadata $resourceMetadata, array $metadata): ResourceMetadata
    {
        foreach (['shortName'] as $property) {
            if (null === $metadata[$property] || null !== $resourceMetadata->{'get'.ucfirst($property)}()) {
                continue;
            }
            $resourceMetadata = $resourceMetadata->{'with'.ucfirst($property)}($metadata[$property]);
        }

        return $resourceMetadata;
    }
}
