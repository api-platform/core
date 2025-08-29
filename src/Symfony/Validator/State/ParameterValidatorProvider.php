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

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\State\ParameterNotFound;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\ParameterParserTrait;
use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates parameters using the Symfony validator.
 */
final class ParameterValidatorProvider implements ProviderInterface
{
    use ParameterParserTrait;

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly ProviderInterface $decorated,
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

        $constraintViolationList = new ConstraintViolationList();
        $parameters = $operation->getParameters() ?? new Parameters();

        if ($operation instanceof HttpOperation) {
            foreach ($operation->getUriVariables() ?? [] as $key => $uriVariable) {
                if ($uriVariable->getValue() instanceof ParameterNotFound) {
                    $uriVariable->setValue($uriVariables[$key] ?? new ParameterNotFound());
                }

                $parameters->add($key, $uriVariable->withKey($key));
            }
        }

        foreach ($parameters as $parameter) {
            if (!$constraints = $parameter->getConstraints()) {
                continue;
            }

            $value = $parameter->getValue();

            if ($value instanceof ParameterNotFound) {
                $value = null;
            }

            $violations = $this->validator->validate($value, $constraints);

            foreach ($violations as $violation) {
                $constraintViolationList->add(new ConstraintViolation(
                    $violation->getMessage(),
                    $violation->getMessageTemplate(),
                    $violation->getParameters(),
                    $violation->getRoot(),
                    $this->getProperty($parameter, $violation),
                    $violation->getInvalidValue(),
                    $violation->getPlural(),
                    $violation->getCode(),
                    $violation->getConstraint(),
                    $violation->getCause()
                ));
            }
        }

        if (0 !== \count($constraintViolationList)) {
            throw new ValidationException($constraintViolationList);
        }

        return $this->decorated->provide($operation, $uriVariables, $context);
    }

    // There's a `property` inside Parameter but it's used for hydra:search only as here we want the parameter name instead
    private function getProperty(Parameter $parameter, ConstraintViolationInterface $violation): string
    {
        $key = $parameter->getKey();

        if (str_contains($key, '[:property]')) {
            return str_replace('[:property]', $violation->getPropertyPath(), $key);
        }

        if (str_contains($key, ':property')) {
            return str_replace(':property', $violation->getPropertyPath(), $key);
        }

        $openApi = $parameter->getOpenApi();
        if (false === $openApi) {
            $openApi = null;
        }

        if ('deepObject' === $openApi?->getStyle() && $p = $violation->getPropertyPath()) {
            return $key.$p;
        }

        return $key;
    }
}
