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

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ApiResource\Error;

/**
 * @internal
 */
final class ErrorProvider implements ProviderInterface
{
    public function __construct(private readonly bool $debug = false)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Error
    {
        if (!($request = $context['request'] ?? null) || !$operation instanceof HttpOperation || null === ($exception = $request->attributes->get('exception'))) {
            throw new \RuntimeException('Not an HTTP request');
        }

        $status = $operation->getStatus() ?? 500;
        $error = Error::createFromException($exception, $status);
        if (!$this->debug && $status >= 500) {
            $error->setDetail('Internal Server Error');
        }

        return $error;
    }
}
