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
 * Resource class not supported exception.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceClassNotSupportedException extends \Exception implements ExceptionInterface
{
}

class_alias(ResourceClassNotSupportedException::class, \ApiPlatform\Core\Exception\ResourceClassNotSupportedException::class);
