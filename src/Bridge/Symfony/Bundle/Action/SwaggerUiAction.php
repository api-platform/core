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

use ApiPlatform\Core\Api\FormatsProviderInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Twig\Environment as TwigEnvironment;

/**
 * Displays the documentation.
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
    private $showWebby;
    private $formats = [];
    private $oauthEnabled;
    private $oauthClientId;
    private $oauthClientSecret;
    private $oauthType;
    private $oauthFlow;
    private $oauthTokenUrl;
    private $oauthAuthorizationUrl;
    private $oauthScopes;
    private $formatsProvider;
    private $swaggerUiEnabled;
    private $reDocEnabled;
    private $graphqlEnabled;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, NormalizerInterface $normalizer, TwigEnvironment $twig, UrlGeneratorInterface $urlGenerator, string $title = '', string $description = '', string $version = '', /* FormatsProviderInterface */ $formatsProvider = [], $oauthEnabled = false, $oauthClientId = '', $oauthClientSecret = '', $oauthType = '', $oauthFlow = '', $oauthTokenUrl = '', $oauthAuthorizationUrl = '', $oauthScopes = [], bool $showWebby = true, bool $swaggerUiEnabled = false, bool $reDocEnabled = false, bool $graphqlEnabled = false)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->normalizer = $normalizer;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
        $this->title = $title;
        $this->showWebby = $showWebby;
        $this->description = $description;
        $this->version = $version;
        $this->oauthEnabled = $oauthEnabled;
        $this->oauthClientId = $oauthClientId;
        $this->oauthClientSecret = $oauthClientSecret;
        $this->oauthType = $oauthType;
        $this->oauthFlow = $oauthFlow;
        $this->oauthTokenUrl = $oauthTokenUrl;
        $this->oauthAuthorizationUrl = $oauthAuthorizationUrl;
        $this->oauthScopes = $oauthScopes;
        $this->swaggerUiEnabled = $swaggerUiEnabled;
        $this->reDocEnabled = $reDocEnabled;
        $this->graphqlEnabled = $graphqlEnabled;

        if (\is_array($formatsProvider)) {
            if ($formatsProvider) {
                // Only trigger notification for non-default argument
                @trigger_error('Using an array as formats provider is deprecated since API Platform 2.3 and will not be possible anymore in API Platform 3', E_USER_DEPRECATED);
            }
            $this->formats = $formatsProvider;

            return;
        }
        if (!$formatsProvider instanceof FormatsProviderInterface) {
            throw new InvalidArgumentException(sprintf('The "$formatsProvider" argument is expected to be an implementation of the "%s" interface.', FormatsProviderInterface::class));
        }

        $this->formatsProvider = $formatsProvider;
    }

    public function __invoke(Request $request)
    {
        // BC check to be removed in 3.0
        if (null !== $this->formatsProvider) {
            $this->formats = $this->formatsProvider->getFormatsFromAttributes(RequestAttributesExtractor::extractAttributes($request));
        }

        $documentation = new Documentation($this->resourceNameCollectionFactory->create(), $this->title, $this->description, $this->version, $this->formats);

        return new Response($this->twig->render('@ApiPlatform/SwaggerUi/index.html.twig', $this->getContext($request, $documentation)));
    }

    /**
     * Gets the base Twig context.
     */
    private function getContext(Request $request, Documentation $documentation): array
    {
        $context = [
            'title' => $this->title,
            'description' => $this->description,
            'formats' => $this->formats,
            'showWebby' => $this->showWebby,
            'swaggerUiEnabled' => $this->swaggerUiEnabled,
            'reDocEnabled' => $this->reDocEnabled,
            'graphqlEnabled' => $this->graphqlEnabled,
        ];

        $swaggerContext = ['spec_version' => $request->query->getInt('spec_version', 2)];
        if ('' !== $baseUrl = $request->getBaseUrl()) {
            $swaggerContext['base_url'] = $baseUrl;
        }

        $swaggerData = [
            'url' => $this->urlGenerator->generate('api_doc', ['format' => 'json']),
            'spec' => $this->normalizer->normalize($documentation, 'json', $swaggerContext),
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
                $swaggerData['operationId'] = sprintf('%s%sCollection', $collectionOperationName, ucfirst($swaggerData['shortName']));
            } elseif (null !== $itemOperationName = $request->attributes->get('_api_item_operation_name')) {
                $swaggerData['operationId'] = sprintf('%s%sItem', $itemOperationName, ucfirst($swaggerData['shortName']));
            } elseif (null !== $subresourceOperationContext = $request->attributes->get('_api_subresource_context')) {
                $swaggerData['operationId'] = $subresourceOperationContext['operationId'];
            }

            [$swaggerData['path'], $swaggerData['method']] = $this->getPathAndMethod($swaggerData);
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
