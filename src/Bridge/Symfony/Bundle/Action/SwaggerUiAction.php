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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Action;

use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Displays the documentation in Swagger UI.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SwaggerUiAction
{
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $normalizer;
    private $twig;
    private $urlGenerator;
    private $title;
    private $description;
    private $version;
    private $formats;
    private $oauthEnabled;
    private $oauthClientId;
    private $oauthClientSecret;
    private $oauthType;
    private $oauthFlow;
    private $oauthTokenUrl;
    private $oauthAuthorizationUrl;
    private $oauthScopes;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, NormalizerInterface $normalizer, \Twig_Environment $twig, UrlGeneratorInterface $urlGenerator, string $title = '', string $description = '', string $version = '', array $formats = [], $oauthEnabled = false, $oauthClientId = '', $oauthClientSecret = '', $oauthType = '', $oauthFlow = '', $oauthTokenUrl = '', $oauthAuthorizationUrl = '', $oauthScopes = [])
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->normalizer = $normalizer;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
        $this->title = $title;
        $this->description = $description;
        $this->version = $version;
        $this->formats = $formats;
        $this->oauthEnabled = $oauthEnabled;
        $this->oauthClientId = $oauthClientId;
        $this->oauthClientSecret = $oauthClientSecret;
        $this->oauthType = $oauthType;
        $this->oauthFlow = $oauthFlow;
        $this->oauthTokenUrl = $oauthTokenUrl;
        $this->oauthAuthorizationUrl = $oauthAuthorizationUrl;
        $this->oauthScopes = $oauthScopes;
    }

    public function __invoke(Request $request)
    {
        $documentation = new Documentation($this->resourceNameCollectionFactory->create(), $this->title, $this->description, $this->version, $this->formats);

        return new Response($this->twig->render('@ApiPlatform/SwaggerUi/index.html.twig', $this->getContext($request, $documentation)));
    }

    /**
     * Gets the base Twig context.
     *
     * @param Request $request
     *
     * @return array
     */
    private function getContext(Request $request, Documentation $documentation): array
    {
        $context = [
            'title' => $this->title,
            'description' => $this->description,
            'formats' => $this->formats,
        ];

        $swaggerData = [
            'url' => $this->urlGenerator->generate('api_doc', ['format' => 'json']),
            'spec' => $this->normalizer->normalize($documentation, 'json', ['base_url' => $request->getBaseUrl()]),
        ];

        $swaggerData['oauth'] = [
            'enabled' => $this->oauthEnabled,
            'clientId' => $this->oauthClientId,
            'clientSecret' => $this->oauthClientSecret,
            'type' => $this->oauthType,
            'flow' => $this->oauthFlow,
            'tokenUrl' => $this->oauthTokenUrl,
            'authorizationUrl' => $this->oauthAuthorizationUrl,
            'scopes' => $this->oauthScopes,
        ];

        if ($request->isMethodSafe(false) && null !== $resourceClass = $request->attributes->get('_api_resource_class')) {
            $swaggerData['id'] = $request->attributes->get('id');
            $swaggerData['queryParameters'] = $request->query->all();

            $metadata = $this->resourceMetadataFactory->create($resourceClass);
            $swaggerData['shortName'] = $metadata->getShortName();

            if (null !== $collectionOperationName = $request->attributes->get('_api_collection_operation_name')) {
                $swaggerData['operationId'] = sprintf('%s%sCollection', $collectionOperationName, $swaggerData['shortName']);
            } elseif (null !== $itemOperationName = $request->attributes->get('_api_item_operation_name')) {
                $swaggerData['operationId'] = sprintf('%s%sItem', $itemOperationName, $swaggerData['shortName']);
            } elseif (null !== $subresourceOperationContext = $request->attributes->get('_api_subresource_context')) {
                $swaggerData['operationId'] = $subresourceOperationContext['operationId'];
            }

            list($swaggerData['path'], $swaggerData['method']) = $this->getPathAndMethod($swaggerData);
        }

        return $context + ['swagger_data' => $swaggerData];
    }

    private function getPathAndMethod(array $swaggerData): array
    {
        foreach ($swaggerData['spec']['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                if ($operation['operationId'] === $swaggerData['operationId']) {
                    return [$path, $method];
                }
            }
        }

        throw new RuntimeException(sprintf('The operation "%s" cannot be found in the Swagger specification.', $swaggerData['operationId']));
    }
}
