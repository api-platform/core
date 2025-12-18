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

namespace Workbench\App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Response;

class CustomHandler extends Handler
{
    public static bool $customRenderCalled = false;

    public function render($request, \Throwable $exception)
    {
        if ($exception instanceof CustomHandlerException) {
            self::$customRenderCalled = true;

            return new Response('Custom Handler Class Response', 419);
        }

        return parent::render($request, $exception);
    }
}
