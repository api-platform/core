<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\JsonLd;

use Dunglas\ApiBundle\Api\IriConverterInterface;
use Dunglas\ApiBundle\Api\ResourceCollectionInterface;
use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Symfony\Component\Routing\RouterInterface;

/**
 * API Entrypoint builder.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class EntrypointBuilder
{
    /**
     * @var ResourceCollectionInterface
     */
    private $resourceCollection;
    /**
     * @var IriConverterInterface
     */
    private $iriConverter;
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        ResourceCollectionInterface $resourceCollection,
        IriConverterInterface $iriConverter,
        RouterInterface $router
    ) {
        $this->resourceCollection = $resourceCollection;
        $this->iriConverter = $iriConverter;
        $this->router = $router;
    }

    /**
     * Gets the entrypoint of the API.
     *
     * return array
     */
    public function getEntrypoint()
    {
        $entrypoint = [
            '@context' => $this->router->generate('api_json_ld_entrypoint_context'),
            '@id' => $this->router->generate('api_json_ld_entrypoint'),
            '@type' => 'Entrypoint',
        ];

        foreach ($this->resourceCollection as $resource) {
            try {
                $entrypoint[lcfirst($resource->getShortName())] = $this->iriConverter->getIriFromResource($resource);
            } catch(InvalidArgumentException $ex) {}
        }

        return $entrypoint;
    }
}
