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

namespace ApiPlatform\JsonApi\Tests\Fixtures;

use ApiPlatform\Metadata\Exception\ErrorCodeSerializableInterface;

class ErrorCodeSerializable extends \Exception implements ErrorCodeSerializableInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getErrorCode(): string
    {
        return '1234';
    }
}
