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

namespace ApiPlatform\Exception;

/**
 * Invalid resource exception.
 *
 * @author Paul Le Corre <paul@lecorre.me>
 */
class InvalidResourceException extends \Exception implements ExceptionInterface
{
}

class_alias(InvalidResourceException::class, \ApiPlatform\Core\Exception\InvalidResourceException::class);
