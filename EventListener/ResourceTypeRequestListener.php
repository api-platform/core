<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\EventListener;

use Dunglas\ApiBundle\Api\ResourceCollectionInterface;
use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Convert the resource type name to a ResourceType instance.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceTypeRequestListener
{
    /**
     * @var ResourceCollectionInterface
     */
    private $resourceTypeCollection;

    public function __construct(ResourceCollectionInterface $resourceTypeCollection)
    {
        $this->resourceTypeCollection = $resourceTypeCollection;
    }

    /**
     * Does the conversion.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->attributes->has('_resource')) {
            return;
        }

        $shortName = $request->attributes->get('_resource');
        $resourceType = $this->resourceTypeCollection->getResourceForShortName($shortName);
        if (!$resourceType) {
            throw new InvalidArgumentException(sprintf('The resource "%s" cannot be found.', $shortName));
        }

        $request->attributes->set('_resource_type', $resourceType);
    }
}
