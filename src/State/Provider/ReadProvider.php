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

namespace ApiPlatform\State\Provider;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Util\CloneTrait;
use ApiPlatform\State\Exception\ProviderNotFoundException;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use ApiPlatform\State\StopwatchAwareInterface;
use ApiPlatform\State\StopwatchAwareTrait;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\State\Util\RequestParser;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Retrieves data from the applicable data provider, based on the current IRI, and sets it as a request parameter called data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ReadProvider implements ProviderInterface, StopwatchAwareInterface
{
    use CloneTrait;
    use OperationRequestInitiatorTrait;
    use StopwatchAwareTrait;
    use UriVariablesResolverTrait;

    public function __construct(
        private readonly ProviderInterface $provider,
        private readonly ?SerializerContextBuilderInterface $serializerContextBuilder = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$operation instanceof HttpOperation) {
            return null;
        }

        $request = ($context['request'] ?? null);
        if (!$operation->canRead()) {
            return null;
        }

        $this->stopwatch?->start('api_platform.provider.read');

        if (null === ($filters = $request?->attributes->get('_api_filters')) && $request) {
            $queryString = RequestParser::getQueryString($request);
            $filters = $queryString ? RequestParser::parseRequestParams($queryString) : null;
        }

        if ($filters) {
            $context['filters'] = $filters;
        }

        $resourceClass = $operation->getClass();

        if ($this->serializerContextBuilder && $request) {
            // Builtin data providers are able to use the serialization context to automatically add join clauses
            $context += $normalizationContext = $this->serializerContextBuilder->createFromRequest($request, true, [
                'resource_class' => $resourceClass,
                'operation' => $operation,
            ]);
            $request->attributes->set('_api_normalization_context', $normalizationContext);
        }

        try {
            $data = $this->provider->provide($operation, $uriVariables, $context);
        } catch (ProviderNotFoundException $e) {
            // In case the dev just forgot to implement it
            $this->logger?->debug('No provider registered for {resource_class}', ['resource_class' => $resourceClass]);
            $data = null;
        }

        if (
            null === $data
            && 'POST' !== $operation->getMethod()
            && ('PUT' !== $operation->getMethod()
                || ($operation instanceof Put && !($operation->getAllowCreate() ?? false))
            )
        ) {
            throw new NotFoundHttpException('Not Found', $e ?? null);
        }

        $request?->attributes->set('data', $data);
        $request?->attributes->set('previous_data', $this->clone($data));

        $this->stopwatch?->stop('api_platform.provider.read');

        return $data;
    }
}
