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
use ApiPlatform\State\Util\ParameterParserTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Validates parameters using the Symfony validator.
 *
 * @implements ProviderInterface<object>
 *
 * @experimental
 */
final class ParameterValidatorProvider implements ProviderInterface
{
    use ParameterParserTrait;
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

            // Basically renames our key from order[:property] to order.* to assign the rule properly (see https://laravel.com/docs/11.x/validation#rule-in)
            if (str_contains($key, '[:property]')) {
                $k = str_replace('[:property]', '', $key);
                $allConstraints[$k.'.*'] = $constraints;
                continue;
            }

            $allConstraints[$key] = $constraints;
        }

        $validator = Validator::make($request->query->all(), $allConstraints);
        if ($validator->fails()) {
            throw $this->getValidationError($validator, new ValidationException($validator));
        }

        return $this->decorated->provide($operation, $uriVariables, $context);
    }
}
