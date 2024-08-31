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

namespace ApiPlatform\Symfony\EventListener;

use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\Exception\InvalidIdentifierException;
use ApiPlatform\Metadata\Exception\InvalidUriVariableException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UriVariablesConverterInterface;
use ApiPlatform\Metadata\Util\CloneTrait;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\State\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Retrieves data from the applicable data provider and sets it as a request parameter called data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ReadListener
{
    use CloneTrait;
    use OperationRequestInitiatorTrait;
    use UriVariablesResolverTrait;

    /**
     * @param ProviderInterface<object> $provider
     */
    public function __construct(
        private readonly ProviderInterface $provider,
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null,
        ?UriVariablesConverterInterface $uriVariablesConverter = null,
    ) {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->uriVariablesConverter = $uriVariablesConverter;
    }

    /**
     * Calls the data provider and sets the data attribute.
     *
     * @throws NotFoundHttpException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!($attributes = RequestAttributesExtractor::extractAttributes($request)) || !$attributes['receive']) {
            return;
        }

        $operation = $this->initializeOperation($request);
        if (!$operation) {
            return;
        }

        if (null === $operation->canRead()) {
            $operation = $operation->withRead($operation->getUriVariables() || $request->isMethodSafe());
        }

        $uriVariables = [];
        if (!$operation instanceof Error && $operation instanceof HttpOperation) {
            try {
                $uriVariables = $this->getOperationUriVariables($operation, $request->attributes->all(), $operation->getClass());
            } catch (InvalidIdentifierException|InvalidUriVariableException $e) {
                if ($operation->canRead()) {
                    throw new NotFoundHttpException('Invalid identifier value or configuration.', $e);
                }
            }
        }

        $request->attributes->set('_api_uri_variables', $uriVariables);
        $this->provider->provide($operation, $uriVariables, [
            'request' => $request,
            'uri_variables' => $uriVariables,
            'resource_class' => $operation->getClass(),
        ]);
    }
}
