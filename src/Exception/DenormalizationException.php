<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Exception;

use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializationExceptionInterface;

/**
 * Denormalization exception.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class DenormalizationException extends \Exception implements ExceptionInterface, SerializationExceptionInterface
{
}
