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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Serializer\ApiGatewayNormalizer;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Util\ContentNegotiationTrait;
use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Generates the API documentation.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class DocumentationAction
{
    use ContentNegotiationTrait;

    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly string $title = '',
        private readonly string $description = '',
        private readonly string $version = '',
        private readonly ?OpenApiFactoryInterface $openApiFactory = null,
        private readonly ?ProviderInterface $provider = null,
        private readonly ?ProcessorInterface $processor = null,
        Negotiator $negotiator = null
    ) {
        $this->negotiator = $negotiator ?? new Negotiator();
    }

    /**
     * @return DocumentationInterface|OpenApi
     */
    public function __invoke(Request $request = null)
    {
        $context = [];
        if (null !== $request) {
            $isGateway = $request->query->getBoolean(ApiGatewayNormalizer::API_GATEWAY);
            $context['api_gateway'] = $isGateway;
            $context['base_url'] = $request->getBaseUrl();
            $request->attributes->set('_api_normalization_context', $request->attributes->get('_api_normalization_context', []) + $context);
            $format = $this->getRequestFormat($request, ['json' => ['application/json'], 'jsonld' => ['application/ld+json'], 'html' => ['text/html']]);

            if ('html' === $format || 'json' === $format && null !== $this->openApiFactory) {
                if ($this->provider && $this->processor) {
                    $context['request'] = $request;
                    $operation = new Get(class: OpenApi::class, provider: fn () => $this->openApiFactory->__invoke($context), normalizationContext: [ApiGatewayNormalizer::API_GATEWAY => $isGateway]);
                    if ('html' === $format) {
                        $operation = $operation->withProcessor('api_platform.swagger_ui.processor')->withWrite(true);
                    }

                    $body = $this->provider->provide($operation, [], $context);

                    return $this->processor->process($body, $operation, [], $context);
                }

                return $this->openApiFactory->__invoke($context);
            }
        }

        if ($this->provider && $this->processor) {
            $context['request'] = $request;
            $operation = new Get(
                class: Documentation::class,
                provider: fn () => new Documentation($this->resourceNameCollectionFactory->create(), $this->title, $this->description, $this->version),
                normalizationContext: [ApiGatewayNormalizer::API_GATEWAY => $isGateway ?? false]
            );
            $body = $this->provider->provide($operation, [], $context);

            return $this->processor->process($body, $operation, [], $context);
        }

        return new Documentation($this->resourceNameCollectionFactory->create(), $this->title, $this->description, $this->version);
    }
}
