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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ErrorPageAction
{
    public function __invoke(Request $request): Response
    {
        $status = $request->attributes->get('status');
        $text = Response::$statusTexts[$status] ?? throw new NotFoundHttpException();

        return new Response(<<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>Error $status</title>
    </head>
    <body><h1>Error $status</h1>$text</body>
</html>
HTML);
    }
}
