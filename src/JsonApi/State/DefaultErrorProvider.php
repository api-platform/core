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

namespace ApiPlatform\JsonApi\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Validator\Exception\ConstraintViolationListAwareExceptionInterface;

/**
 * @internal
 */
final class DefaultErrorProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object
    {
        $exception = $context['previous_data'];

        if ($exception instanceof ConstraintViolationListAwareExceptionInterface) {
            return $exception->getConstraintViolationList();
        }

        return $exception;
    }
}
