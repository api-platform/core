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

namespace ApiPlatform\Laravel\Controller;

use ApiPlatform\Documentation\Documentation;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Util\ContentNegotiationTrait;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Serializer\ApiGatewayNormalizer;
use ApiPlatform\OpenApi\Serializer\LegacyOpenApiNormalizer;
use ApiPlatform\OpenApi\Serializer\OpenApiNormalizer;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generates the API documentation.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class DocumentationController
{
    use ContentNegotiationTrait;

    /**
     * @param array<string, string[]>             $documentationFormats
     * @param ProviderInterface<object>           $provider
     * @param ProcessorInterface<mixed, Response> $processor
     */
    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly string $title = '',
        private readonly string $description = '',
        private readonly string $version = '',
        private readonly ?OpenApiFactoryInterface $openApiFactory = null,
        private readonly ?ProviderInterface $provider = null,
        private readonly ?ProcessorInterface $processor = null,
        ?Negotiator $negotiator = null,
        private readonly array $documentationFormats = [OpenApiNormalizer::JSON_FORMAT => ['application/vnd.openapi+json'], OpenApiNormalizer::FORMAT => ['application/json']],
        private readonly bool $swaggerUiEnabled = true,
    ) {
        $this->negotiator = $negotiator ?? new Negotiator();
    }

    public function __invoke(Request $request): Response
    {
        $context = [
            'api_gateway' => $request->query->getBoolean(ApiGatewayNormalizer::API_GATEWAY),
            'base_url' => $request->getBaseUrl(),
            'spec_version' => (string) $request->query->get(LegacyOpenApiNormalizer::SPEC_VERSION),
        ];
        $request->attributes->set('_api_normalization_context', $request->attributes->get('_api_normalization_context', []) + $context);
        // We want to find the format early on, this code is also executed later on by the ContentNegotiationProvider.
        $this->addRequestFormats($request, $this->documentationFormats);
        $format = $this->getRequestFormat($request, $this->documentationFormats);

        if ('html' === $format || OpenApiNormalizer::FORMAT === $format || OpenApiNormalizer::JSON_FORMAT === $format || OpenApiNormalizer::YAML_FORMAT === $format) {
            return $this->getOpenApiDocumentation($context, $format, $request);
        }

        return $this->getHydraDocumentation($context, $request);
    }

    /**
     * @param array<string,mixed> $context
     */
    private function getOpenApiDocumentation(array $context, string $format, Request $request): Response
    {
        $context['request'] = $request;
        $operation = new Get(
            class: OpenApi::class,
            read: true,
            serialize: true,
            provider: fn () => $this->openApiFactory->__invoke($context),
            normalizationContext: [
                ApiGatewayNormalizer::API_GATEWAY => $context['api_gateway'] ?? null,
                LegacyOpenApiNormalizer::SPEC_VERSION => $context['spec_version'] ?? null,
            ],
            outputFormats: $this->documentationFormats
        );

        if ('html' === $format && $this->swaggerUiEnabled) {
            $operation = $operation->withProcessor('api_platform.swagger_ui.processor')->withWrite(true);
        }

        return $this->processor->process($this->provider->provide($operation, [], $context), $operation, [], $context);
    }

    /**
     * TODO: the logic behind the Hydra Documentation is done in a ApiPlatform\Hydra\Serializer\DocumentationNormalizer.
     * We should transform this to a provider, it'd improve performances also by a bit.
     *
     * @param array<string,mixed> $context
     */
    private function getHydraDocumentation(array $context, Request $request): Response
    {
        $context['request'] = $request;
        $operation = new Get(
            class: Documentation::class,
            read: true,
            serialize: true,
            provider: fn () => new Documentation($this->resourceNameCollectionFactory->create(), $this->title, $this->description, $this->version)
        );

        return $this->processor->process($this->provider->provide($operation, [], $context), $operation, [], $context);
    }
}
