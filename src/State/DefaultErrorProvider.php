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

namespace ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Symfony\Validator\Exception\ConstraintViolationListAwareExceptionInterface;

class DefaultErrorProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object
    {
        $exception = $context['previous_data'];

        if ($exception instanceof ConstraintViolationListAwareExceptionInterface && '_api_validation_errors_jsonapi' === $context['operation_name']) {
            return $exception->getConstraintViolationList();
        }

        return $exception;
    }
}
