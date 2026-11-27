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
    public const TYPE_MISMATCH_ERROR = '13ff6cd3-d9ac-451a-af4a-846e8e36056f';

    public function __construct(string $message, mixed $value, string $path, private readonly string $violationCode)
    {
        parent::__construct($message, 0, null, get_debug_type($value), null, $path, true);
    }

    public function getViolationCode(): string
    {
        return $this->violationCode;
    }
}
