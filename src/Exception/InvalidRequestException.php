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

namespace ApiPlatform\Core\Exception;

use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;

/**
 * Invalid Request exception.
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 */
class InvalidRequestException extends InvalidArgumentException implements RequestExceptionInterface
{
}
