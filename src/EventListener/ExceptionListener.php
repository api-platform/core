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

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener as BaseExceptionListener;
use Psr\Log\LoggerInterface;

/**
 * Handles requests errors.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ExceptionListener extends BaseExceptionListener
{
    /**
     * @var array
     */
    private $exceptionToStatus;

    /**
     * @param mixed $controller
     * @param LoggerInterface $logger
     * @param array $exceptionToStatus
     */
    public function __construct($controller, LoggerInterface $logger = null, array $exceptionToStatus = null)
    {
        parent::__construct($controller, $logger);

        $this->exceptionToStatus = $exceptionToStatus ?? [];
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();
        // Normalize exceptions only for routes managed by API Platform
        if (
            'html' === $request->getRequestFormat('') ||
            (!$request->attributes->has('_api_resource_class') && !$request->attributes->has('_api_respond') && !$request->attributes->has('_graphql'))
        ) {
            return;
        }

        parent::onKernelException($event);
    }

    /**
     * {@inheritDoc}
     */
    protected function logException(\Exception $exception, $message)
    {
        if (null === $this->logger) {
            parent::logException($exception, $message);

            return;
        }

        $exceptionClass = get_class($exception);
        $statusCode = null;

        foreach ($this->exceptionToStatus as $class => $status) {
            if (is_a($exceptionClass, $class, true)) {
                $statusCode = $status;

                break;
            }
        }

        if (null === $statusCode || $statusCode >= 500) {
            parent::logException($exception, $message);

            return;
        }

        $this->logger->error($message, ['exception' => $exception]);
    }
}
