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

namespace ApiPlatform\Symfony\Bundle\ArgumentResolver;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Symfony\EventListener\DenyAccessListener;
use ApiPlatform\Symfony\EventListener\DeserializeListener;
use ApiPlatform\Symfony\EventListener\ReadListener;
use ApiPlatform\Symfony\EventListener\ValidateListener;
use ApiPlatform\Symfony\EventListener\WriteListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @experimental
 */
final class ApiResourceArgumentResolver implements ValueResolverInterface
{
    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, private readonly HttpKernelInterface $httpKernel, private readonly DeserializeListener $deserializeListener, private ReadListener $readListener, private DenyAccessListener $denyAccessListener, private ValidateListener $validateListener, private WriteListener $writeListener)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $apiResource = $argument->getAttributesOfType(ApiResource::class);

        if (!$apiResource) {
            return [];
        }

        $metadata = $this->resourceMetadataCollectionFactory->create($argument->getType());
        $operation = $metadata->getOperation($request->attributes->get('_route'));

        // Check if the route param doesn't exist we don't want to fetch identifiers
        $routeParams = $request->attributes->get('_route_params');
        $uriVariable = array_keys($operation->getUriVariables())[0] ?? '';
        if (!isset($routeParams[$uriVariable])) {
            $operation = $operation->withUriVariables([]);
        }

        $request->attributes->set('_api_operation', $operation);
        $request->attributes->set('_api_operation_name', $operation->getName());
        $request->attributes->set('_api_resource_class', $operation->getClass());

        $requestEvent = new RequestEvent($this->httpKernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $this->denyAccessListener->onSecurity($requestEvent);
        $this->deserializeListener->onKernelRequest($requestEvent);
        $this->denyAccessListener->onSecurityPostDenormalize($requestEvent);
        $this->readListener->onKernelRequest($requestEvent);
        $viewEvent = new ViewEvent($this->httpKernel, $request, HttpKernelInterface::MAIN_REQUEST, $request->attributes->get('data'));
        $this->validateListener->onKernelView($viewEvent);
        $this->denyAccessListener->onSecurityPostValidation($viewEvent);
        $this->writeListener->onKernelView($viewEvent);

        $operation = $metadata->getOperation();
        $request->attributes->set('_api_operation', $operation);
        $request->attributes->set('_api_operation_name', $operation->getName());

        return [$request->attributes->get('data')];
    }
}
