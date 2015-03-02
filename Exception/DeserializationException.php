<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Exception;

use Symfony\Component\Serializer\Exception\Exception;

/**
 * Deserialization exception
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class DeserializationException extends \Exception implements Exception
{
}
