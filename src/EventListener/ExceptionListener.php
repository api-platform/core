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

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener as BaseExceptionListener;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * Handles requests errors.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ExceptionListener
{
    private $exceptionListener;

    public function __construct($controller, LoggerInterface $logger = null, $debug = false, string $charset = null, $fileLinkFormat = null)
    {
        $this->exceptionListener = new BaseExceptionListener($controller, $logger, $debug, $charset, $fileLinkFormat);
    }

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $request = $event->getRequest();
        // Normalize exceptions only for routes managed by API Platform
        if (
            'html' === $request->getRequestFormat('') ||
            !((RequestAttributesExtractor::extractAttributes($request)['respond'] ?? $request->attributes->getBoolean('_api_respond', false)) || $request->attributes->getBoolean('_graphql', false))
        ) {
            return;
        }

        // unwrap the exception thrown in handler for Symfony Messenger >= 4.3
        $exception = $event->getException();
        if ($exception instanceof HandlerFailedException) {
            /** @var \Throwable $previousException */
            $previousException = $exception->getPrevious();
            if (!$previousException instanceof \Exception) {
                throw $previousException;
            }

            $event->setException($previousException);
        }

        $this->exceptionListener->onKernelException($event);
    }
}
