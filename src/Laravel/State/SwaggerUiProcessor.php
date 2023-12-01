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

namespace ApiPlatform\Laravel\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Options;
use ApiPlatform\OpenApi\Serializer\NormalizeOperationNameTrait;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Http\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final class SwaggerUiProcessor implements ProcessorInterface
{
    use NormalizeOperationNameTrait;

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator, private readonly NormalizerInterface $normalizer, private readonly Options $openApiOptions, private readonly array $formats = [], private readonly ?string $oauthClientId = null, private readonly ?string $oauthClientSecret = null, private readonly bool $oauthPkce = false)
    {
    }

    /**
     * @param OpenApi $openApi
     */
    public function process(mixed $openApi, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $request = $context['request'] ?? null;

        $swaggerContext = [
            'formats' => $this->formats,
            'title' => $openApi->getInfo()->getTitle(),
            'description' => $openApi->getInfo()->getDescription(),
            'originalRoute' => $request->attributes->get('_api_original_route', $request->attributes->get('_route')),
            'originalRouteParams' => $request->attributes->get('_api_original_route_params', $request->attributes->get('_route_params', [])),
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
        ];

        $status = 200;
        $requestedOperation = $request?->attributes->get('_api_requested_operation') ?? null;
        if ($request->isMethodSafe() && $requestedOperation && $requestedOperation->getName()) {
            $swaggerData['id'] = $request->get('id');
            $swaggerData['queryParameters'] = $request->query->all();

            $swaggerData['shortName'] = $requestedOperation->getShortName();
            $swaggerData['operationId'] = $this->normalizeOperationName($requestedOperation->getName());

            [$swaggerData['path'], $swaggerData['method']] = $this->getPathAndMethod($swaggerData);
            $status = $requestedOperation->getStatus() ?? $status;
        }

        return new Response(view('api-platform::swagger-ui', $swaggerContext + ['swagger_data' => $swaggerData]), 200);
    }
}
