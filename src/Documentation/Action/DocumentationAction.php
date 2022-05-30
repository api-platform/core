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

namespace ApiPlatform\Documentation\Action;

use ApiPlatform\Core\Api\FormatsProviderInterface;
use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface as LegacyOpenApiFactoryInterface;
use ApiPlatform\Documentation\Documentation;
use ApiPlatform\Documentation\DocumentationInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;

/**
 * Generates the API documentation.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class DocumentationAction
{
    private $resourceNameCollectionFactory;
    private $title;
    private $description;
    private $version;
    private $formats;
    private $formatsProvider;
    private $swaggerVersions;
    private $openApiFactory;

    /**
     * @param int[]                                                 $swaggerVersions
     * @param mixed|array|FormatsProviderInterface                  $formatsProvider
     * @param LegacyOpenApiFactoryInterface|OpenApiFactoryInterface $openApiFactory
     */
    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, string $title = '', string $description = '', string $version = '', array $swaggerVersions = [2, 3], OpenApiFactoryInterface $openApiFactory = null)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->title = $title;
        $this->description = $description;
        $this->version = $version;
        $this->swaggerVersions = $swaggerVersions;
        $this->openApiFactory = $openApiFactory;
    }

    public function __invoke(Request $request = null): DocumentationInterface
    {
        if (null !== $request) {
            $context = ['base_url' => $request->getBaseUrl(), 'spec_version' => $request->query->getInt('spec_version', $this->swaggerVersions[0] ?? 3)];
            if ($request->query->getBoolean('api_gateway')) {
                $context['api_gateway'] = true;
            }
            $request->attributes->set('_api_normalization_context', $request->attributes->get('_api_normalization_context', []) + $context);

            $attributes = RequestAttributesExtractor::extractAttributes($request);
        }

        if ('json' === $request->getRequestFormat() && null !== $this->openApiFactory && 3 === ($context['spec_version'] ?? null)) {
            return $this->openApiFactory->__invoke($context ?? []);
        }

        return new Documentation($this->resourceNameCollectionFactory->create(), $this->title, $this->description, $this->version, $this->formats);
    }
}
