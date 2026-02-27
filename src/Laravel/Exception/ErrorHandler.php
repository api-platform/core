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

namespace ApiPlatform\Laravel\Exception;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler as ExceptionsHandler;

class ErrorHandler extends ExceptionsHandler
{
    public function __construct(
        Container $container,
        private readonly ErrorRenderer $errorRenderer,
        private readonly ?ExceptionHandler $decorated = null,
    ) {
        parent::__construct($container);
    }

    public function render($request, \Throwable $exception)
    {
        if ($this->errorRenderer->shouldRender($request, $exception)) {
            $response = $this->errorRenderer->render($request, $exception);

            if ($response) {
                return $response;
            }
        }

        // If it's not an API operation, or the renderer wasn't able to generate
        // a response, first check if any renderable callbacks on this ErrorHandler
        // instance can handle the exception (issue #7466).
        $response = $this->renderViaCallbacks($request, $exception);

        if ($response) {
            return $response;
        }

        // If no callbacks handled it, delegate to the decorated handler if available
        // to preserve custom exception handler classes (issue #7058).
        return $this->decorated ? $this->decorated->render($request, $exception) : parent::render($request, $exception);
    }
}
