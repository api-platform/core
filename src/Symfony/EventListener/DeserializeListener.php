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

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\State\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * Updates the entity retrieved by the data provider with data contained in the request body.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DeserializeListener
{
    use OperationRequestInitiatorTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'deserialize';

    /**
     * @param ProviderInterface<object> $provider
     */
    public function __construct(
        private ProviderInterface $provider,
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null,
    ) {
        $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
    }

    /**
     * Deserializes the data sent in the requested format.
     *
     * @throws UnsupportedMediaTypeHttpException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $method = $request->getMethod();
        $operation = $this->initializeOperation($request);

        if (
            !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !$attributes['receive']
            || !$operation
        ) {
            return;
        }

        if (null === $operation->canDeserialize() && $operation instanceof HttpOperation) {
            $operation = $operation->withDeserialize(\in_array($method, ['POST', 'PUT', 'PATCH'], true));
        }

        if (!$operation->canDeserialize()) {
            return;
        }

        $data = $this->provider->provide($operation, $request->attributes->get('_api_uri_variables') ?? [], [
            'request' => $request,
            'uri_variables' => $request->attributes->get('_api_uri_variables') ?? [],
            'resource_class' => $operation->getClass(),
        ]);

        $request->attributes->set('data', $data);
    }
}
