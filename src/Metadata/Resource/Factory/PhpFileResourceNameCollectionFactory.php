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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\Extractor\ResourceExtractorInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;

/**
 * @internal
 */
final class PhpFileResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    public function __construct(
        private readonly ResourceExtractorInterface $metadataExtractor,
        private readonly ?ResourceNameCollectionFactoryInterface $decorated = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function create(): ResourceNameCollection
    {
        $classes = [];

        if ($this->decorated) {
            foreach ($this->decorated->create() as $resourceClass) {
                $classes[$resourceClass] = true;
            }
        }

        foreach ($this->metadataExtractor->getResources() as $resource) {
            $resourceClass = $resource->getClass();

            if (null === $resourceClass) {
                continue;
            }

            $classes[$resourceClass] = true;
        }

        return new ResourceNameCollection(array_keys($classes));
    }
}
