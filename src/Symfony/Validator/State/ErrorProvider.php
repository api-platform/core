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
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Validator\Exception\ConstraintViolationListAwareExceptionInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @internal
 */
final class ErrorProvider implements ProviderInterface
{
    public function __construct()
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ConstraintViolationListInterface|\Throwable
    {
        if (!($request = $context['request'] ?? null) || !$operation instanceof HttpOperation) {
            throw new \RuntimeException('Not an HTTP request');
        }

        $exception = $request->attributes->get('exception');
        $exception->setStatus($operation->getStatus());

        if ('jsonapi' === $request->getRequestFormat() && $exception instanceof ConstraintViolationListAwareExceptionInterface) {
            return $exception->getConstraintViolationList();
        }

        return $exception;
    }
}
