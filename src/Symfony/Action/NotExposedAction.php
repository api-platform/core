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

namespace ApiPlatform\Symfony\Action;

use ApiPlatform\Metadata\Exception\NotExposedHttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 * An action which always returns HTTP 404 Not Found with an explanation for why the operation is not exposed.
 */
final class NotExposedAction
{
    public function __invoke(Request $request): never
    {
        $message = 'This route does not aim to be called.';
        if ('api_genid' === $request->attributes->get('_route')) {
            $message = 'This route is not exposed on purpose. It generates an IRI for a collection resource without identifier nor item operation.';
        }

        throw new NotExposedHttpException($message);
    }
}
