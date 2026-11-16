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

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\State\Exception\ParameterNotSupportedException;
use ApiPlatform\State\Exception\ProviderNotFoundException;
use ApiPlatform\State\Pagination\PaginationOptions;
use ApiPlatform\State\ParameterNotFound;
use ApiPlatform\State\ParameterProvider\PreservesUriVariableInterface;
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

    public function __construct(private readonly ?ProviderInterface $decorated = null, private readonly ?ContainerInterface $locator = null, private readonly ?PaginationOptions $paginationOptions = null)
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
        if ($parameters) {
            $parameters = clone $parameters;
            $operation = $operation->withParameters($parameters);
        }

        if ($operation instanceof HttpOperation && true === $operation->getStrictQueryParameterValidation()) {
            $keys = [];
            foreach ($parameters as $parameter) {
                $keys[] = $parameter->getKey();
            }

            if ($operation instanceof CollectionOperationInterface) {
                $keys = [...$keys, ...$this->getPaginationParameterNames($operation)];
            }

            foreach (array_keys($request->attributes->get('_api_query_parameters')) as $key) {
                if (!\in_array($key, $keys, true)) {
                    throw new ParameterNotSupportedException($key);
                }
            }
        }

        $context = ['operation' => $operation] + $context;

        $snapshot = [];
        foreach ($parameters ?? [] as $parameterKey => $parameter) {
            $snapshot[] = [$parameterKey, $parameter];
        }

        foreach ($snapshot as [$parameterKey, $original]) {
            $current = $this->findParameter($operation->getParameters(), $parameterKey, $original) ?? $original;
            $values = $this->getParameterValues($current, $request, $context);
            $value = $this->extractParameterValues($current, $values);

            if (null !== ($default = $current->getDefault() ?? $current->getSchema()['default'] ?? null) && $value instanceof ParameterNotFound) {
                $value = $default;
            }

            $parameter = $current->withValue($value);
            $result = $this->callParameterProvider($operation, $parameter, $values, $context);

            if ($result instanceof Parameter) {
                $parameter = $result;
            } elseif ($result instanceof Operation) {
                $operation = $result;
                $returned = $this->findParameter($operation->getParameters(), $parameterKey, $parameter);
                if (null !== $returned && $returned !== $current) {
                    $parameter = $returned;
                }
            }

            $context['operation'] = $operation = $operation->withParameters(($operation->getParameters() ?? new Parameters())->with($parameterKey, $parameter));
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
        $mimeType = trim(strtolower(explode(';', (string) $request->headers->get('Content-Type', ''))[0]));

        if ('application/x-www-form-urlencoded' === $mimeType) {
            return '' === $content ? [] : RequestParser::parseRequestParams($content);
        }

        if (str_contains($mimeType, 'json')) {
            if ('' === $content) {
                return [];
            }

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
     * @return list<string>
     */
    private function getPaginationParameterNames(CollectionOperationInterface&HttpOperation $operation): array
    {
        if (!$this->paginationOptions) {
            return [];
        }

        $names = [];

        if ($operation->getPaginationEnabled() ?? $this->paginationOptions->isPaginationEnabled()) {
            $names[] = $this->paginationOptions->getPaginationPageParameterName();

            if ($operation->getPaginationClientItemsPerPage() ?? $this->paginationOptions->getClientItemsPerPage()) {
                $names[] = $this->paginationOptions->getItemsPerPageParameterName();
            }
        }

        if ($operation->getPaginationClientEnabled() ?? $this->paginationOptions->isPaginationClientEnabled()) {
            $names[] = $this->paginationOptions->getPaginationClientEnabledParameterName();
        }

        if ($operation->getPaginationClientPartial() ?? $this->paginationOptions->isClientPartialPaginationEnabled()) {
            $names[] = $this->paginationOptions->getPartialPaginationParameterName();
        }

        return $names;
    }

    /**
     * TODO: uriVariables could be a Parameters instance, it'd make things easier.
     *
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    private function handlePathParameters(HttpOperation $operation, array &$uriVariables, array $context): HttpOperation
    {
        foreach ($operation->getUriVariables() ?? [] as $key => $original) {
            $uriVariable = $original->withKey($key);
            if ($uriVariable->getSecurity() && !$uriVariable->getProvider()) {
                $uriVariable = $uriVariable->withProvider(ReadLinkParameterProvider::class);
            }

            $values = $uriVariables;

            if (!\array_key_exists($key, $uriVariables)) {
                continue;
            }

            $value = $uriVariables[$key];

            if (($default = $uriVariable->getDefault() ?? $uriVariable->getSchema()['default'] ?? false) && ($value instanceof ParameterNotFound || !$value)) {
                $value = $default;
            }

            $uriVariable = $uriVariable->withValue($value);
            $result = $this->callParameterProvider($operation, $uriVariable, $values, $context);

            if ($result instanceof Parameter) {
                $uriVariable = $result;
            } elseif ($result instanceof HttpOperation) {
                $context['operation'] = $operation = $result;
                $returned = $operation->getUriVariables()[$key] ?? null;
                if (null !== $returned && $returned !== $original) {
                    $uriVariable = $returned;
                }
            }

            if (!$uriVariable->getValue() instanceof ParameterNotFound && !$this->preservesUriVariable($operation, $uriVariable)) {
                $uriVariables[$key] = $uriVariable->getValue();
            }
        }

        return $operation;
    }

    /**
     * A provider may resolve the parameter to a resource, leaving an object in the value where the
     * resource is queried with an identifier.
     */
    private function preservesUriVariable(Operation $operation, Parameter $parameter): bool
    {
        $provider = $this->resolveProvider($operation, $parameter->getProvider());

        return $provider instanceof PreservesUriVariableInterface && $provider->preservesUriVariable($parameter);
    }

    private function resolveProvider(Operation $operation, mixed $provider): mixed
    {
        if (!\is_string($provider)) {
            return $provider;
        }

        if (!$this->locator->has($provider)) {
            throw new ProviderNotFoundException(\sprintf('Provider "%s" not found on operation "%s"', $provider, $operation->getName()));
        }

        return $this->locator->get($provider);
    }

    private function findParameter(?Parameters $parameters, string $key, Parameter $reference): ?Parameter
    {
        foreach ($parameters ?? [] as $name => $parameter) {
            if ($name === $key
                && $parameter::class === $reference::class
                && $parameter->getProperty() === $reference->getProperty()
                && $parameter->getProperties() === $reference->getProperties()
            ) {
                return $parameter;
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed> $context
     */
    private function callParameterProvider(Operation $operation, Parameter $parameter, mixed $values, array $context): Operation|Parameter|null
    {
        if ($parameter->getValue() instanceof ParameterNotFound) {
            return null;
        }

        if (null === ($provider = $parameter->getProvider())) {
            return null;
        }

        $result = \is_callable($provider)
            ? $provider($parameter, $values, $context)
            : $this->resolveProvider($operation, $provider)->provide($parameter, $values, $context);

        return $result instanceof Operation || $result instanceof Parameter ? $result : null;
    }
}
