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

namespace ApiPlatform\Elasticsearch\Exception;

use ApiPlatform\Exception\ExceptionInterface;

/**
 * Non unique identifier exception.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class NonUniqueIdentifierException extends \Exception implements ExceptionInterface
{
}
