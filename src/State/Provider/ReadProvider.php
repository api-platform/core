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
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\State\Exception\ProviderNotFoundException;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\State\Util\RequestParser;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Retrieves data from the applicable data provider, based on the current IRI, and sets it as a request parameter called data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ReadProvider implements ProviderInterface
{
    use CloneTrait;
    use OperationRequestInitiatorTrait;
    use UriVariablesResolverTrait;

    public function __construct(
        private readonly ProviderInterface $provider,
        private readonly ?SerializerContextBuilderInterface $serializerContextBuilder = null,
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

        if (null === $filters = $request?->attributes->get('_api_filters')) {
            $queryString = RequestParser::getQueryString($request);
            $filters = $queryString ? RequestParser::parseRequestParams($queryString) : null;
        }

        if ($filters) {
            $context['filters'] = $filters;
        }

        if ($this->serializerContextBuilder && $request) {
            // Builtin data providers are able to use the serialization context to automatically add join clauses
            $context += $this->serializerContextBuilder->createFromRequest($request, true, [
                'resource_class' => $operation->getClass(),
                'operation' => $operation,
            ]);
        }

        try {
            $data = $this->provider->provide($operation, $uriVariables, $context);
        } catch (ProviderNotFoundException $e) {
            $data = null;
        }

        if (
            null === $data
            && 'POST' !== $operation->getMethod()
            && ('PUT' !== $operation->getMethod()
                || ($operation instanceof Put && !($operation->getAllowCreate() ?? false))
            )
        ) {
            throw new NotFoundHttpException('Not Found');
        }

        $request?->attributes->set('data', $data);
        $request?->attributes->set('previous_data', $this->clone($data));

        return $data;
    }
}
