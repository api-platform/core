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

namespace ApiPlatform\State\Exception;

use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

/**
 * A well-formed value the API refuses, reported as a validation violation (422) at its path.
 */
final class UnprocessableValueException extends NotNormalizableValueException
{
}
