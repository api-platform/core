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
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\Exception\ParameterNotSupportedException;
use ApiPlatform\State\Exception\ProviderNotFoundException;
use ApiPlatform\State\ParameterNotFound;
use ApiPlatform\State\ParameterProvider\ReadLinkParameterProvider;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\StopwatchAwareInterface;
use ApiPlatform\State\StopwatchAwareTrait;
use ApiPlatform\State\Util\ParameterParserTrait;
use ApiPlatform\State\Util\RequestParser;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * Loops over parameters to:
 *   - compute its values set as extra properties from the Parameter object (`_api_values`)
 *   - call the Parameter::provider if any and updates the Operation
 */
final class ParameterProvider implements ProviderInterface, StopwatchAwareInterface
{
    use ParameterParserTrait;
    use StopwatchAwareTrait;

    public function __construct(private readonly ?ProviderInterface $decorated = null, private readonly ?ContainerInterface $locator = null)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $this->stopwatch?->start('api_platform.provider.parameter');
        $request = $context['request'] ?? null;
        if ($request && null === $request->attributes->get('_api_query_parameters')) {
            // RFC 10008: the HTTP QUERY method carries its parameters in the request body
            // rather than in the URI query string.
            if ($operation instanceof HttpOperation && HttpOperation::METHOD_QUERY === $operation->getMethod()) {
                $request->attributes->set('_api_query_parameters', $this->parseQueryParametersFromBody($request));
            } else {
                $queryString = RequestParser::getQueryString($request);
                $request->attributes->set('_api_query_parameters', $queryString ? RequestParser::parseRequestParams($queryString) : []);
            }
        }

        if ($request && null === $request->attributes->get('_api_header_parameters')) {
            $request->attributes->set('_api_header_parameters', $request->headers->all());
        }

        $parameters = $operation->getParameters();

        if ($operation instanceof HttpOperation && true === $operation->getStrictQueryParameterValidation()) {
            $keys = [];
            foreach ($parameters as $parameter) {
                $keys[] = $parameter->getKey();
            }

            foreach (array_keys($request->attributes->get('_api_query_parameters')) as $key) {
                if (!\in_array($key, $keys, true)) {
                    throw new ParameterNotSupportedException($key);
                }
            }
        }

        $context = ['operation' => $operation] + $context;

        foreach ($parameters ?? [] as $parameter) {
            $values = $this->getParameterValues($parameter, $request, $context);
            $value = $this->extractParameterValues($parameter, $values);
            // we force API Platform's value extraction, use _api_query_parameters or _api_header_parameters if you need to set a value
            if (isset($parameter->getExtraProperties()['_api_values'])) {
                unset($parameter->getExtraProperties()['_api_values']);
            }

            if (null !== ($default = $parameter->getDefault() ?? $parameter->getSchema()['default'] ?? null) && $value instanceof ParameterNotFound) {
                $value = $default;
            }

            $parameter->setValue($value);
            $context['operation'] = $operation = $this->callParameterProvider($operation, $parameter, $values, $context);
        }

        if ($parameters) {
            $operation = $operation->withParameters($parameters);
        }

        if ($operation instanceof HttpOperation) {
            $operation = $this->handlePathParameters($operation, $uriVariables, $context);
        }

        $request?->attributes->set('_api_operation', $operation);
        $context['operation'] = $operation;
        $this->stopwatch?->stop('api_platform.provider.parameter');

        return $this->decorated?->provide($operation, $uriVariables, $context);
    }

    /**
     * Reads the parameters of an HTTP QUERY request (RFC 10008) from its body, negotiated
     * by content type: "application/x-www-form-urlencoded" shares the query-string grammar,
     * any JSON-based media type decodes to an associative array.
     *
     * @return array<string, mixed>
     */
    private function parseQueryParametersFromBody(Request $request): array
    {
        $content = (string) $request->getContent();
        if ('' === $content) {
            return [];
        }

        $mimeType = trim(strtolower(explode(';', (string) $request->headers->get('Content-Type', ''))[0]));

        if ('application/x-www-form-urlencoded' === $mimeType) {
            return RequestParser::parseRequestParams($content);
        }

        if (str_contains($mimeType, 'json')) {
            try {
                $params = json_decode($content, true, flags: \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new BadRequestHttpException('The QUERY request body is not valid JSON.', $e);
            }

            if (!\is_array($params)) {
                throw new BadRequestHttpException('The QUERY request body must decode to a JSON object.');
            }

            return $params;
        }

        throw new UnsupportedMediaTypeHttpException(\sprintf('The content type "%s" is not supported for the HTTP QUERY method. Use "application/x-www-form-urlencoded" or a JSON-based media type.', $mimeType ?: 'none'), headers: ['Accept-Query' => 'application/x-www-form-urlencoded, application/json']);
    }

    /**
     * TODO: uriVariables could be a Parameters instance, it'd make things easier.
     *
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    private function handlePathParameters(HttpOperation $operation, array $uriVariables, array $context): HttpOperation
    {
        foreach ($operation->getUriVariables() ?? [] as $key => $uriVariable) {
            $uriVariable = $uriVariable->withKey($key);
            if ($uriVariable->getSecurity() && !$uriVariable->getProvider()) {
                $uriVariable = $uriVariable->withProvider(ReadLinkParameterProvider::class);
            }

            $values = $uriVariables;

            if (!\array_key_exists($key, $uriVariables)) {
                continue;
            }

            $value = $uriVariables[$key];
            // we force API Platform's value extraction, use _api_query_parameters or _api_header_parameters if you need to set a value
            if (isset($uriVariable->getExtraProperties()['_api_values'])) {
                unset($uriVariable->getExtraProperties()['_api_values']);
            }

            if (($default = $uriVariable->getDefault() ?? $uriVariable->getSchema()['default'] ?? false) && ($value instanceof ParameterNotFound || !$value)) {
                $value = $default;
            }

            $uriVariable->setValue($value);
            if (($op = $this->callParameterProvider($operation, $uriVariable, $values, $context)) instanceof HttpOperation) {
                $context['operation'] = $operation = $op;
            }
        }

        return $operation;
    }

    /**
     * @param array<string,mixed> $context
     */
    private function callParameterProvider(Operation $operation, Parameter $parameter, mixed $values, array $context): Operation
    {
        if ($parameter->getValue() instanceof ParameterNotFound) {
            return $operation;
        }

        if (null === ($provider = $parameter->getProvider())) {
            return $operation;
        }

        if (\is_callable($provider)) {
            if (($op = $provider($parameter, $values, $context)) instanceof Operation) {
                $operation = $op;
            }

            return $operation;
        }

        if (\is_string($provider)) {
            if (!$this->locator->has($provider)) {
                throw new ProviderNotFoundException(\sprintf('Provider "%s" not found on operation "%s"', $provider, $operation->getName()));
            }

            $provider = $this->locator->get($provider);
        }

        if (($op = $provider->provide($parameter, $values, $context)) instanceof Operation) {
            $operation = $op;
        }

        return $operation;
    }
}
