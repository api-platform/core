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

use ApiPlatform\Metadata\HeaderParameterInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Exception\ProviderNotFoundException;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\RequestParser;
use Psr\Container\ContainerInterface;

class ParameterProvider implements ProviderInterface
{
    public function __construct(private readonly ?ProviderInterface $decorated = null, private readonly ?ContainerInterface $locator = null)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (null === ($request = $context['request'])) {
            return $this->decorated?->provide($operation, $uriVariables, $context);
        }

        if (null === $request->attributes->get('_api_query_parameters')) {
            $queryString = RequestParser::getQueryString($request);
            $request->attributes->set('_api_query_parameters', $queryString ? RequestParser::parseRequestParams($queryString) : []);
        }

        if (null === $request->attributes->get('_api_header_parameters')) {
            $request->attributes->set('_api_header_parameters', $request->headers->all());
        }

        $context = ['operation' => $operation] + $context;

        foreach ($operation->getParameters() ?? [] as $key => $parameter) {
            if (null === ($provider = $parameter->getProvider())) {
                continue;
            }

            $parameters = $parameter instanceof HeaderParameterInterface ? $request->attributes->get('_api_header_parameters') : $request->attributes->get('_api_query_parameters');
            if (!isset($parameters[$key])) {
                continue;
            }

            if (\is_callable($provider) && (($op = $provider($parameter, $parameters, $context)) instanceof HttpOperation)) {
                $operation = $op;
                $request->attributes->set('_api_operation', $operation);
                $context['operation'] = $operation;
                continue;
            }

            if (!\is_string($provider) || !$this->locator->has($provider)) {
                throw new ProviderNotFoundException(sprintf('Provider "%s" not found on operation "%s"', $provider, $operation->getName()));
            }

            /** @var ProviderInterface $providerInstance */
            $providerInstance = $this->locator->get($provider);
            if (($op = $providerInstance->provide($parameter, $parameters, $context)) instanceof HttpOperation) {
                $operation = $op;
                $request->attributes->set('_api_operation', $operation);
                $context['operation'] = $operation;
            }
        }

        return $this->decorated?->provide($operation, $uriVariables, $context);
    }
}
