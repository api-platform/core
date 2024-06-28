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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\ParameterParserTrait;
use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates parameters using the Symfony validator.
 *
 * @experimental
 */
final class ParameterValidatorProvider implements ProviderInterface
{
    use ParameterParserTrait;

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly ProviderInterface $decorated
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!($request = $context['request']) instanceof Request) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        $operation = $request->attributes->get('_api_operation') ?? $operation;
        foreach ($operation->getParameters() ?? [] as $parameter) {
            if (!$constraints = $parameter->getConstraints()) {
                continue;
            }

            $key = $this->getParameterFlattenKey($parameter->getKey(), $this->extractParameterValues($parameter, $request, $context));
            $value = $parameter->getExtraProperties()['_api_values'][$key] ?? null;
            $violations = $this->validator->validate($value, $constraints);
            if (0 !== \count($violations)) {
                $constraintViolationList = new ConstraintViolationList();
                foreach ($violations as $violation) {
                    $propertyPath = $key !== $parameter->getKey() ? $key.$violation->getPropertyPath() : ($parameter->getProperty() ?? $key);
                    $constraintViolationList->add(new ConstraintViolation(
                        $violation->getMessage(),
                        $violation->getMessageTemplate(),
                        $violation->getParameters(),
                        $violation->getRoot(),
                        $propertyPath,
                        $violation->getInvalidValue(),
                        $violation->getPlural(),
                        $violation->getCode(),
                        $violation->getConstraint(),
                        $violation->getCause()
                    ));
                }

                throw new ValidationException($constraintViolationList);
            }
        }

        return $this->decorated->provide($operation, $uriVariables, $context);
    }
}
