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

use ApiPlatform\Documentation\Documentation;
use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\State\ProviderInterface;

/**
 * When an HTML request is sent we provide a swagger ui documentation.
 *
 * @internal
 */
final class SwaggerUiProvider implements ProviderInterface
{
    public function __construct(private readonly ProviderInterface $decorated, private readonly OpenApiFactoryInterface $openApiFactory)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // We went through the DocumentationAction
        if (OpenApi::class === $operation->getClass()) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        if (
            !($operation instanceof HttpOperation)
            || !($request = $context['request'] ?? null)
            || 'html' !== $request->getRequestFormat()
        ) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        if (!$request->attributes->has('_api_requested_operation')) {
            $request->attributes->set('_api_requested_operation', $operation);
        }

        // We need to call our operation provider just in case it fails
        // when it fails we'll get an Error and we'll fix the status accordingly
        // @see features/main/content_negotiation.feature:119
        // DocumentationAction has no content negotation as well we want HTML so render swagger ui
        if (!$operation instanceof Error && Documentation::class !== $operation->getClass()) {
            $this->decorated->provide($operation, $uriVariables, $context);
        }

        $swaggerUiOperation = new Get(
            class: OpenApi::class,
            processor: 'api_platform.swagger_ui.processor',
            validate: false,
            read: false,
            write: true, // force write so that our processor gets called
            status: $operation->getStatus()
        );

        // save our operation
        $request->attributes->set('_api_operation', $swaggerUiOperation);

        return $this->openApiFactory->__invoke($context);
    }
}
