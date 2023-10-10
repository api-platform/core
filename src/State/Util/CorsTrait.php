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

namespace ApiPlatform\State\Util;

use Symfony\Component\HttpFoundation\Request;

/**
 * CORS utils.
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait CorsTrait
{
    public function isPreflightRequest(Request $request): bool
    {
        return $request->isMethod('OPTIONS') && $request->headers->has('Access-Control-Request-Method');
    }
}
