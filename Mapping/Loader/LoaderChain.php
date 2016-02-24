<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Mapping\Loader;

use Dunglas\ApiBundle\Mapping\ClassMetadata;

/**
 * Calls multiple {@link LoaderInterface} instances in a chain.
 *
 * This class accepts multiple instances of LoaderInterface to be passed to the
 * constructor. When {@link loadClassMetadata()} is called, the same method is called
 * in <em>all</em> of these loaders, regardless of whether any of them was
 * successful or not.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class LoaderChain implements LoaderInterface
{
    /**
     * @var LoaderInterface[]
     */
    private $loaders;

    /**
     * Accepts a list of LoaderInterface instances.
     *
     * @param LoaderInterface[] $loaders An array of LoaderInterface instances
     *
     * @throws \InvalidArgumentException If any of the loaders does not implement LoaderInterface
     */
    public function __construct(array $loaders)
    {
        foreach ($loaders as $loader) {
            if (!$loader instanceof LoaderInterface) {
                throw new \InvalidArgumentException(sprintf('Class %s is expected to implement LoaderInterface', get_class($loader)));
            }
        }

        $this->loaders = $loaders;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(
        ClassMetadata $metadata,
        array $normalizationGroups = null,
        array $denormalizationGroups = null,
        array $validationGroups = null
    ) {
        $success = false;

        foreach ($this->loaders as $loader) {
            $success = $loader->loadClassMetadata(
                $metadata,
                $normalizationGroups,
                $denormalizationGroups,
                $validationGroups
            ) || $success;
        }

        $this->checkClassMetaData($metadata);

        return $success;
    }

    /**
     * @param ClassMetadata $classMetaData
     *
     * @throws \RunTimeException
     */
    private function checkClassMetaData(ClassMetadata $classMetaData)
    {
        if (!$classMetaData->getIdentifier()) {
            throw new \RunTimeException(sprintf('Class "%s" have no identifier.', $classMetaData->getName()));
        }
    }
}
