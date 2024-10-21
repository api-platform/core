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
use ApiPlatform\State\Exception\ProviderNotFoundException;
use ApiPlatform\State\ParameterNotFound;
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

        $parameters = $operation->getParameters();
        foreach ($parameters ?? [] as $parameter) {
            $extraProperties = $parameter->getExtraProperties();
            unset($extraProperties['_api_values']);
            $parameters->add($parameter->getKey(), $parameter = $parameter->withExtraProperties($extraProperties));

            $context = ['operation' => $operation] + $context;
            $values = $this->getParameterValues($parameter, $request, $context);
            $value = $this->extractParameterValues($parameter, $values);

            if (($default = $parameter->getSchema()['default'] ?? false) && ($value instanceof ParameterNotFound || !$value)) {
                $value = $default;
            }

            if ($value instanceof ParameterNotFound) {
                continue;
            }

            $parameters->add($parameter->getKey(), $parameter = $parameter->withExtraProperties(
                $parameter->getExtraProperties() + ['_api_values' => $value]
            ));

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
                throw new ProviderNotFoundException(\sprintf('Provider "%s" not found on operation "%s"', $provider, $operation->getName()));
            }

            /** @var ParameterProviderInterface $providerInstance */
            $providerInstance = $this->locator->get($provider);
            if (($op = $providerInstance->provide($parameter, $values, $context)) instanceof Operation) {
                $operation = $op;
            }
        }

        if ($parameters) {
            $operation = $operation->withParameters($parameters);
        }
        $request?->attributes->set('_api_operation', $operation);
        $context['operation'] = $operation;

        return $this->decorated?->provide($operation, $uriVariables, $context);
    }
}
