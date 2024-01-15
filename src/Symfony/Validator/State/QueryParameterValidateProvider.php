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

namespace ApiPlatform\Symfony\Validator\State;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\ParameterValidator\ParameterValidator;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\RequestParser;

final class QueryParameterValidateProvider implements ProviderInterface
{
    public function __construct(private readonly ?ProviderInterface $decorated, private readonly ParameterValidator $parameterValidator)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (
            !$operation instanceof HttpOperation
            || !($request = $context['request'] ?? null)
            || !$request->isMethodSafe()
            || 'GET' !== $request->getMethod()
        ) {
            return $this->decorated?->provide($operation, $uriVariables, $context);
        }

        if (!($operation->getQueryParameterValidationEnabled() ?? true) || !$operation instanceof CollectionOperationInterface) {
            return $this->decorated?->provide($operation, $uriVariables, $context);
        }

        $queryString = RequestParser::getQueryString($request);
        $queryParameters = $queryString ? RequestParser::parseRequestParams($queryString) : [];
        $class = $operation->getClass();
        if (($options = $operation->getStateOptions()) && $options instanceof Options && $options->getEntityClass()) {
            $class = $options->getEntityClass();
        }

        $this->parameterValidator->validateFilters($class, $operation->getFilters() ?? [], $queryParameters);

        return $this->decorated?->provide($operation, $uriVariables, $context);
    }
}
