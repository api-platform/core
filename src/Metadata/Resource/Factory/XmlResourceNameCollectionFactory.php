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

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Metadata\XmlExtractor;

/**
 * Creates a resource name collection from a XML {@see Resource} configuration files.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class XmlResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    private $extractor;
    private $decorated;

    public function __construct(XmlExtractor $extractor, ResourceNameCollectionFactoryInterface $decorated = null)
    {
        $this->extractor = $extractor;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function create(): ResourceNameCollection
    {
        $classes = [];
        if (null !== $this->decorated) {
            foreach ($this->decorated->create() as $resourceClass) {
                $classes[$resourceClass] = true;
            }
        }

        foreach ($this->extractor->getResources() as $key => $value) {
            $classes[$key] = true;
        }

        return new ResourceNameCollection(array_keys($classes));
    }
}
