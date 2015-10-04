<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\JsonLd;

use Dunglas\ApiBundle\Api\IriConverterInterface;
use Dunglas\ApiBundle\Api\ResourceCollectionInterface;
use Dunglas\ApiBundle\Api\ResourceInterface;
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
     * @return array
     */
    public function getEntrypoint()
    {
        $entrypoint = [
            '@context' => $this->router->generate('api_jsonld_context', ['shortName' => 'Entrypoint']),
            '@id' => $this->router->generate('api_jsonld_entrypoint'),
            '@type' => 'Entrypoint',
        ];

        foreach ($this->resourceCollection as $resource) {
            if (!empty($resource->getCollectionOperations())) {
                try {
                    $entrypoint[lcfirst($resource->getShortName())] = $this->iriConverter->getIriFromResource($resource);
                } catch (InvalidArgumentException $ex) {
                    if ($this->hasGetCollectionOperation($resource)) {
                        throw $ex;
                    }
                }
            }
        }

        return $entrypoint;
    }

    /**
     * Returns true if at least one GET collection operation exists for the given resource.
     *
     * @param ResourceInterface $resource
     *
     * @return bool
     */
    private function hasGetCollectionOperation(ResourceInterface $resource)
    {
        foreach ($resource->getCollectionOperations() as $operation) {
            $methods = $operation->getRoute()->getMethods();
            if (empty($methods) || in_array('GET', $methods)) {
                return true;
            }
        }

        return false;
    }
}
