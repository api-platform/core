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

namespace ApiPlatform\Core\Tests\Mock\Exception;

use ApiPlatform\Exception\ErrorCodeSerializableInterface;

class ErrorCodeSerializable extends \Exception implements ErrorCodeSerializableInterface
{
    public static function getErrorCode(): string
    {
        return '1234';
    }
}
