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

namespace ApiPlatform\Symfony\Bundle\SwaggerUi;

use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Options;
use ApiPlatform\OpenApi\Serializer\NormalizeOperationNameTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Twig\Environment as TwigEnvironment;

/**
 * Displays the swaggerui interface.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class SwaggerUiAction
{
    use NormalizeOperationNameTrait;

    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private readonly ?TwigEnvironment $twig, private readonly UrlGeneratorInterface $urlGenerator, private readonly NormalizerInterface $normalizer, private readonly OpenApiFactoryInterface $openApiFactory, private readonly Options $openApiOptions, private readonly SwaggerUiContext $swaggerUiContext, private readonly array $formats = [], private readonly ?string $oauthClientId = null, private readonly ?string $oauthClientSecret = null, private readonly bool $oauthPkce = false)
    {
        if (null === $this->twig) {
            throw new \RuntimeException('The documentation cannot be displayed since the Twig bundle is not installed. Try running "composer require symfony/twig-bundle".');
        }
    }

    public function __invoke(Request $request): Response
    {
        $openApi = $this->openApiFactory->__invoke(['base_url' => $request->getBaseUrl() ?: '/']);

        $swaggerContext = [
            'formats' => $this->formats,
            'title' => $openApi->getInfo()->getTitle(),
            'description' => $openApi->getInfo()->getDescription(),
            'showWebby' => $this->swaggerUiContext->isWebbyShown(),
            'swaggerUiEnabled' => $this->swaggerUiContext->isSwaggerUiEnabled(),
            'reDocEnabled' => $this->swaggerUiContext->isRedocEnabled(),
            'graphQlEnabled' => $this->swaggerUiContext->isGraphQlEnabled(),
            'graphiQlEnabled' => $this->swaggerUiContext->isGraphiQlEnabled(),
            'graphQlPlaygroundEnabled' => $this->swaggerUiContext->isGraphQlPlaygroundEnabled(),
            'assetPackage' => $this->swaggerUiContext->getAssetPackage(),
        ];

        $swaggerData = [
            'url' => $this->urlGenerator->generate('api_doc', ['format' => 'json']),
            'spec' => $this->normalizer->normalize($openApi, 'json', []),
            'oauth' => [
                'enabled' => $this->openApiOptions->getOAuthEnabled(),
                'type' => $this->openApiOptions->getOAuthType(),
                'flow' => $this->openApiOptions->getOAuthFlow(),
                'tokenUrl' => $this->openApiOptions->getOAuthTokenUrl(),
                'authorizationUrl' => $this->openApiOptions->getOAuthAuthorizationUrl(),
                'scopes' => $this->openApiOptions->getOAuthScopes(),
                'clientId' => $this->oauthClientId,
                'clientSecret' => $this->oauthClientSecret,
                'pkce' => $this->oauthPkce,
            ],
            'extraConfiguration' => $this->swaggerUiContext->getExtraConfiguration(),
        ];

        if ($request->isMethodSafe() && null !== $resourceClass = $request->attributes->get('_api_resource_class')) {
            $swaggerData['id'] = $request->attributes->get('id');
            $swaggerData['queryParameters'] = $request->query->all();

            $metadata = $this->resourceMetadataFactory->create($resourceClass)->getOperation($request->attributes->get('_api_operation_name'));

            $swaggerData['shortName'] = $metadata->getShortName();
            $swaggerData['operationId'] = $this->normalizeOperationName($metadata->getName());

            [$swaggerData['path'], $swaggerData['method']] = $this->getPathAndMethod($swaggerData);
        }

        return new Response($this->twig->render('@ApiPlatform/SwaggerUi/index.html.twig', $swaggerContext + ['swagger_data' => $swaggerData]));
    }

    private function getPathAndMethod(array $swaggerData): array
    {
        foreach ($swaggerData['spec']['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                if (($operation['operationId'] ?? null) === $swaggerData['operationId']) {
                    return [$path, $method];
                }
            }
        }

        throw new RuntimeException(sprintf('The operation "%s" cannot be found in the Swagger specification.', $swaggerData['operationId']));
    }
}
