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
    private ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory;
    private string $title;
    private string $description;
    private string $version;
    private OpenApiFactoryInterface $openApiFactory;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, string $title = '', string $description = '', string $version = '', OpenApiFactoryInterface $openApiFactory = null)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->title = $title;
        $this->description = $description;
        $this->version = $version;
        $this->openApiFactory = $openApiFactory;
    }

    public function __invoke(Request $request = null): DocumentationInterface
    {
        if (null !== $request) {
            $context = ['base_url' => $request->getBaseUrl()];
            if ($request->query->getBoolean('api_gateway')) {
                $context['api_gateway'] = true;
            }
            $request->attributes->set('_api_normalization_context', $request->attributes->get('_api_normalization_context', []) + $context);

            $attributes = RequestAttributesExtractor::extractAttributes($request);
        }

        if ('json' === $request->getRequestFormat() && null !== $this->openApiFactory) {
            return $this->openApiFactory->__invoke($context ?? []);
        }

        return new Documentation($this->resourceNameCollectionFactory->create(), $this->title, $this->description, $this->version);
    }
}
