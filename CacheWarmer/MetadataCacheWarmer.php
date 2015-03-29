<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\CacheWarmer;

use Dunglas\JsonLdApiBundle\JsonLd\ResourceCollectionInterface;
use Dunglas\JsonLdApiBundle\Mapping\ClassMetadataFactory;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms metadata cache of registered resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class MetadataCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var ResourceCollectionInterface
     */
    private $resourceCollection;
    /**
     * @var ClassMetadataFactory
     */
    private $classMetadataFactory;

    public function __construct(ResourceCollectionInterface $resourceCollection, ClassMetadataFactory $classMetadataFactory)
    {
        $this->resourceCollection = $resourceCollection;
        $this->classMetadataFactory = $classMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        foreach ($this->resourceCollection as $resource) {
            $this->classMetadataFactory->getMetadataFor(
                $resource->getEntityClass(),
                $resource->getNormalizationGroups(),
                $resource->getDenormalizationGroups(),
                $resource->getValidationGroups()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }
}
