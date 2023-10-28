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

namespace ApiPlatform\Problem\Serializer;

use ApiPlatform\Exception\ErrorCodeSerializableInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;

trait ErrorNormalizerTrait
{
    private function getErrorMessage($object, array $context, bool $debug = false): string
    {
        $message = $object->getMessage();

        if ($debug) {
            return $message;
        }

        if ($object instanceof FlattenException) {
            $statusCode = $context['statusCode'] ?? $object->getStatusCode();
            if ($statusCode >= 500 && $statusCode < 600) {
                $message = Response::$statusTexts[$statusCode] ?? Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR];
            }
        }

        return $message;
    }

    private function getErrorCode(object $object): ?string
    {
        if ($object instanceof FlattenException) {
            $exceptionClass = $object->getClass();
        } else {
            $exceptionClass = $object::class;
        }

        if (is_a($exceptionClass, ErrorCodeSerializableInterface::class, true)) {
            return $exceptionClass::getErrorCode();
        }

        return null;
    }
}
