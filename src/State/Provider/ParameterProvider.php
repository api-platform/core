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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\State\Exception\ProviderNotFoundException;
use ApiPlatform\State\ParameterProviderInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\ParameterParserTrait;
use ApiPlatform\State\Util\RequestParser;
use Psr\Container\ContainerInterface;

/**
 * Loops over parameters to:
 *   - compute its values set as extra properties from the Parameter object (`_api_values`)
 *   - call the Parameter::provider if any and updates the Operation
 *
 * @experimental
 */
final class ParameterProvider implements ProviderInterface
{
    use ParameterParserTrait;

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
        $p = $operation->getParameters() ?? [];
        $parameters = $p instanceof Parameters ? iterator_to_array($p) : $p;
        foreach ($parameters as $parameter) {
            $key = $parameter->getKey();
            $values = $this->extractParameterValues($parameter, $request, $context);
            $key = $this->getParameterFlattenKey($key, $values);

            if (!isset($values[$key])) {
                continue;
            }

            $parameters[$parameter->getKey()] = $parameter = $parameter->withExtraProperties(
                $parameter->getExtraProperties() + ['_api_values' => [$key => $values[$key]]]
            );

            if (null === ($provider = $parameter->getProvider())) {
                continue;
            }

            if (\is_callable($provider)) {
                if (($op = $provider($parameter, $values, $context)) instanceof Operation) {
                    $operation = $op;
                }

                continue;
            }

            if (!\is_string($provider) || !$this->locator->has($provider)) {
                throw new ProviderNotFoundException(sprintf('Provider "%s" not found on operation "%s"', $provider, $operation->getName()));
            }

            /** @var ParameterProviderInterface $providerInstance */
            $providerInstance = $this->locator->get($provider);
            if (($op = $providerInstance->provide($parameter, $values, $context)) instanceof Operation) {
                $operation = $op;
            }
        }

        $operation = $operation->withParameters(new Parameters($parameters));
        $request?->attributes->set('_api_operation', $operation);
        $context['operation'] = $operation;

        return $this->decorated?->provide($operation, $uriVariables, $context);
    }
}
