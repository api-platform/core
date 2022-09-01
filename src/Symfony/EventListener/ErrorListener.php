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

namespace ApiPlatform\Symfony\EventListener;

use ApiPlatform\Action\ExceptionAction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\EventListener\ErrorListener as SymfonyErrorListener;

/**
 * This error listener extends the Symfony one in order to add
 * the `_api_operation` attribute when the request is duplicated.
 * It will later be used to retrieve the exceptionToStatus from the operation ({@see ExceptionAction}).
 */
final class ErrorListener extends SymfonyErrorListener
{
    protected function duplicateRequest(\Throwable $exception, Request $request): Request
    {
        $dup = parent::duplicateRequest($exception, $request);

        if ($request->attributes->has('_api_operation')) {
            $dup->attributes->set('_api_operation', $request->attributes->get('_api_operation'));
        }

        // TODO: remove legacy layer in 3.0
        if ($request->attributes->has('_api_exception_to_status')) {
            $dup->attributes->set('_api_exception_to_status', $request->attributes->get('_api_exception_to_status'));
        }

        return $dup;
    }
}
