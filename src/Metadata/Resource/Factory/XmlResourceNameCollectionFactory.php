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
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Creates a resource name collection from a XML {@see Resource} configuration files.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class XmlResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    const RESOURCE_SCHEMA = __DIR__.'/../../schema/metadata.xsd';

    private $paths;
    private $decorated;

    /**
     * @param string[]                                    $paths
     * @param ResourceNameCollectionFactoryInterface|null $decorated
     */
    public function __construct(array $paths, ResourceNameCollectionFactoryInterface $decorated = null)
    {
        $this->paths = $paths;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create() : ResourceNameCollection
    {
        $classes = [];
        if (null !== $this->decorated) {
            foreach ($this->decorated->create() as $resourceClass) {
                $classes[$resourceClass] = true;
            }
        }

        foreach ($this->paths as $path) {
            try {
                $doc = XmlUtils::loadFile($path, self::RESOURCE_SCHEMA);
            } catch (\InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            }

            foreach ($doc->getElementsByTagName('resource') as $resource) {
                $classes[$resource->getAttribute('class')] = true;
            }
        }

        return new ResourceNameCollection(array_keys($classes));
    }
}
