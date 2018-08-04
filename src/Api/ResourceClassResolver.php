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

namespace ApiPlatform\Core\Api;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class ResourceClassResolver implements ResourceClassResolverInterface
{
    use ClassInfoTrait;

    private $resourceNameCollectionFactory;
    private $localIsResourceClassCache = [];

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceClass($value, string $resourceClass = null, bool $strict = false): string
    {
        $type = \is_object($value) && !$value instanceof \Traversable ? $this->getObjectClass($value) : $resourceClass;
        $resourceClass = $resourceClass ?? $type;

        if (null === $resourceClass) {
            throw new InvalidArgumentException(sprintf('No resource class found.'));
        }

        if (
            null === $type
            || ((!$strict || $resourceClass === $type) && $isResourceClass = $this->isResourceClass($type))
        ) {
            return $resourceClass;
        }

        if (
            ($isResourceClass ?? $this->isResourceClass($type))
            || (is_subclass_of($type, $resourceClass) && $this->isResourceClass($resourceClass))
        ) {
            return $type;
        }

        throw new InvalidArgumentException(sprintf('No resource class found for object of type "%s".', $type));
    }

    /**
     * {@inheritdoc}
     */
    public function isResourceClass(string $type): bool
    {
        if (isset($this->localIsResourceClassCache[$type])) {
            return $this->localIsResourceClassCache[$type];
        }

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            if ($type === $resourceClass) {
                return $this->localIsResourceClassCache[$type] = true;
            }
        }

        return $this->localIsResourceClassCache[$type] = false;
    }
}
