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

namespace ApiPlatform\Laravel\State;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ParameterNotFound;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Validates parameters using the Laravel validator.
 *
 * @implements ProviderInterface<object>
 *
 * @experimental
 */
final class ParameterValidatorProvider implements ProviderInterface
{
    use ValidationErrorTrait;

    /**
     * @param ProviderInterface<object> $decorated
     */
    public function __construct(
        private readonly ?ProviderInterface $decorated = null,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!($request = $context['request'] ?? null) instanceof Request) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        $operation = $request->attributes->get('_api_operation') ?? $operation;
        if (!($operation->getQueryParameterValidationEnabled() ?? true)) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        $allConstraints = [];

        foreach ($operation->getParameters() ?? [] as $parameter) {
            if (!$constraints = $parameter->getConstraints()) {
                continue;
            }

            $key = $parameter->getKey();
            if (null === $key) {
                throw new RuntimeException('A parameter must have a defined key.');
            }

            $value = $parameter->getValue();
            if ($value instanceof ParameterNotFound) {
                $value = null;
            }

            // Laravel Validator requires dot notation for nested rules (e.g., "sort.isActive"),
            // not nested arrays. We convert HTTP bracket syntax "sort[isActive]" to "sort.isActive".
            $ruleKey = str_replace(['[', ']'], ['.', ''], $key);

            $allConstraints[$ruleKey] = $constraints;
        }

        $validator = Validator::make($request->query->all(), $allConstraints);

        if ($validator->fails()) {
            throw $this->getValidationError($validator, new ValidationException($validator));
        }

        return $this->decorated->provide($operation, $uriVariables, $context);
    }
}
