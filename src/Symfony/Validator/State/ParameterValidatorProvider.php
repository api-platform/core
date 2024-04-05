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
    public function __construct(
        private readonly ProviderInterface $decorated,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $body = $this->decorated->provide($operation, $uriVariables, $context);
        if (!$context['request'] instanceof Request) {
            return $body;
        }

        $operation = $context['request']->attributes->get('_api_operation');
        foreach ($operation->getParameters() ?? [] as $parameter) {
            if (!$constraints = $parameter->getConstraints()) {
                continue;
            }

            $value = $parameter->getExtraProperties()['_api_values'][$parameter->getKey()] ?? null;
            $violations = $this->validator->validate($value, $constraints);
            if (0 !== \count($violations)) {
                $constraintViolationList = new ConstraintViolationList();
                foreach ($violations as $violation) {
                    $constraintViolationList->add(new ConstraintViolation(
                        $violation->getMessage(),
                        $violation->getMessageTemplate(),
                        $violation->getParameters(),
                        $violation->getRoot(),
                        $parameter->getProperty() ?? $parameter->getKey(),
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

        return $body;
    }
}
