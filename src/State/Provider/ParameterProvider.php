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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\State\Exception\ProviderNotFoundException;
use ApiPlatform\State\ParameterProviderInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\RequestParser;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Loops over parameters to:
 *   - compute its values set as extra properties from the Parameter object (`_api_values`)
 *   - call the Parameter::provider if any and updates the Operation
 *
 * @experimental
 */
final class ParameterProvider implements ProviderInterface
{
    public function __construct(private readonly ?ProviderInterface $decorated = null, private readonly ?ContainerInterface $locator = null)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $context['request'] ?? null;
        if ($request && null === $request->attributes->get('_api_query_parameters')) {
            $queryString = RequestParser::getQueryString($request);
            $request->attributes->set('_api_query_parameters', $queryString ? RequestParser::parseRequestParams($queryString) : []);
        }

        if ($request && null === $request->attributes->get('_api_header_parameters')) {
            $request->attributes->set('_api_header_parameters', $request->headers->all());
        }

        $context = ['operation' => $operation] + $context;
        $parameters = $operation->getParameters() ?? [];
        $operationParameters = $parameters instanceof Parameters ? iterator_to_array($parameters) : $parameters;
        foreach ($operationParameters as $parameter) {
            $key = $parameter->getKey();
            $parameters = $this->extractParameterValues($parameter, $request, $context);
            $parsedKey = explode('[:property]', $key);

            if (isset($parsedKey[0]) && isset($parameters[$parsedKey[0]])) {
                $key = $parsedKey[0];
            }

            if (!isset($parameters[$key])) {
                continue;
            }

            $operationParameters[$parameter->getKey()] = $parameter = $parameter->withExtraProperties(
                $parameter->getExtraProperties() + ['_api_values' => [$key => $parameters[$key]]]
            );

            if (null === ($provider = $parameter->getProvider())) {
                continue;
            }

            if (\is_callable($provider)) {
                if (($op = $provider($parameter, $parameters, $context)) instanceof Operation) {
                    $operation = $op;
                }

                continue;
            }

            if (!\is_string($provider) || !$this->locator->has($provider)) {
                throw new ProviderNotFoundException(sprintf('Provider "%s" not found on operation "%s"', $provider, $operation->getName()));
            }

            /** @var ParameterProviderInterface $providerInstance */
            $providerInstance = $this->locator->get($provider);
            if (($op = $providerInstance->provide($parameter, $parameters, $context)) instanceof Operation) {
                $operation = $op;
            }
        }

        $operation = $operation->withParameters(new Parameters($operationParameters));
        $request?->attributes->set('_api_operation', $operation);
        $context['operation'] = $operation;

        return $this->decorated?->provide($operation, $uriVariables, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function extractParameterValues(Parameter $parameter, ?Request $request, array $context)
    {
        if ($request) {
            return $parameter instanceof HeaderParameterInterface ? $request->attributes->get('_api_header_parameters') : $request->attributes->get('_api_query_parameters');
        }

        // GraphQl
        return $context['args'] ?? [];
    }
}
