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

namespace ApiPlatform\Core\JsonLd;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;

/**
 * JSON-LD context builder interface.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ContextBuilderInterface
{
    const HYDRA_NS = 'http://www.w3.org/ns/hydra/core#';
    const RDF_NS = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    const RDFS_NS = 'http://www.w3.org/2000/01/rdf-schema#';
    const XML_NS = 'http://www.w3.org/2001/XMLSchema#';
    const OWL_NS = 'http://www.w3.org/2002/07/owl#';

    /**
     * Gets the base context.
     *
     * @param int $referenceType
     *
     * @return array
     */
    public function getBaseContext(int $referenceType = UrlGeneratorInterface::ABS_PATH): array;

    /**
     * Builds the JSON-LD context for the entrypoint.
     *
     * @param int $referenceType
     *
     * @return array
     */
    public function getEntrypointContext(int $referenceType = UrlGeneratorInterface::ABS_PATH): array;

    /**
     * Builds the JSON-LD context for the given resource.
     *
     * @param string $resourceClass
     * @param int    $referenceType
     *
     * @throws ResourceClassNotFoundException
     *
     * @return array
     */
    public function getResourceContext(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH): array;

    /**
     * Gets the URI of the given resource context.
     *
     * @param string $resourceClass
     * @param int    $referenceType
     *
     * @return string
     */
    public function getResourceContextUri(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH): string;
}
