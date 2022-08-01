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

use Symfony\Component\HttpFoundation\Request;

final class ErrorListener extends \Symfony\Component\HttpKernel\EventListener\ErrorListener
{
    protected function duplicateRequest(\Throwable $exception, Request $request): Request
    {
        $dup =  parent::duplicateRequest($exception, $request);
        $dup->attributes->set('_api_operation', $request->attributes->get('_api_operation'));

        return $dup;
    }
}
