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

use Dunglas\ApiBundle\Api\ResourceCollectionInterface;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\JsonLd\Event\ContextBuilderEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * JSON-LD Context Builder.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ContextBuilder
{
    const HYDRA_NS = 'http://www.w3.org/ns/hydra/core#';
    const RDF_NS = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    const RDFS_NS = 'http://www.w3.org/2000/01/rdf-schema#';
    const XML_NS = 'http://www.w3.org/2001/XMLSchema#';
    const OWL_NS = 'http://www.w3.org/2002/07/owl#';

    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var ResourceCollectionInterface
     */
    private $resourceCollection;

    /**
     * @var bool|string The type of reference to be generated (one of the {@see UrlGeneratorInterface} constants)
     */
    private $contextReferenceType;

    public function __construct(
        RouterInterface $router,
        EventDispatcherInterface $eventDispatcher,
        ResourceCollectionInterface $resourceCollection,
        $contextReferenceType
    ) {
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
        $this->resourceCollection = $resourceCollection;
        $this->contextReferenceType = $contextReferenceType;
    }

    /**
     * Builds the JSON-LD context for the entrypoint.
     *
     * @return array
     */
    public function getEntrypointContext()
    {
        $context = $this->getBaseContext();

        foreach ($this->resourceCollection as $resource) {
            $resourceName = lcfirst($resource->getShortName());

            $context[$resourceName] = [
                '@id' => 'Entrypoint/'.$resourceName,
                '@type' => '@id',
            ];
        }

        return $context;
    }

    /**
     * @param ResourceInterface $resource
     * @param array             $normalizationContext
     *
     * @return array|string
     */
    public function getResourceContext(ResourceInterface $resource, array $normalizationContext)
    {
        if (isset($normalizationContext['jsonld_context_embedded'])) {
            return $this->getContext($resource);
        }

        return $this->getContextUri($resource);
    }

    /**
     * Builds the JSON-LD context for the given resource.
     *
     * @param ResourceInterface|null $resource
     *
     * @return array
     */
    public function getContext(ResourceInterface $resource = null)
    {
        $context = $this->getBaseContext();
        $event = new ContextBuilderEvent($context, $resource);
        $this->eventDispatcher->dispatch(Event\Events::CONTEXT_BUILDER, $event);

        return $event->getContext();
    }

    /**
     * Gets the context URI for the given resource.
     *
     * @param ResourceInterface $resource
     *
     * @return string
     */
    public function getContextUri(ResourceInterface $resource)
    {
        return $this->router->generate('api_jsonld_context', ['shortName' => $resource->getShortName()], $this->getContextReferenceType());
    }

    /**
     * Translates the string reference types to the constant values of the UrlGeneratorInterface.
     *
     * @return bool|string
     */
    private function getContextReferenceType () {
        switch ($this->contextReferenceType) {
            case "absolute_url":
                return UrlGeneratorInterface::ABSOLUTE_URL;
            case "absolute_path":
                return UrlGeneratorInterface::ABSOLUTE_PATH;
            case "relative_path":
                return UrlGeneratorInterface::RELATIVE_PATH;
            case "network_path":
                return UrlGeneratorInterface::NETWORK_PATH;
            default:
                break;
        }
    }

    /**
     * Gets the base context.
     *
     * @return array
     */
    private function getBaseContext()
    {
        return [
            '@vocab' => $this->router->generate('api_hydra_vocab', [], RouterInterface::ABSOLUTE_URL).'#',
            'hydra' => self::HYDRA_NS,
        ];
    }
}
