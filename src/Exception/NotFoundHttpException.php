<?php

/*
 *  This file is part of the API Platform project.
 *
 *  (c) Kévin Dunglas <dunglas@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException as SymfonyNotFoundHttpException;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
class NotFoundHttpException extends SymfonyNotFoundHttpException implements ExceptionInterface
{

}
