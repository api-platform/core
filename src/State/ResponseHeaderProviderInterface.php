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

namespace ApiPlatform\State;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\ResponseHeader;

/**
 * Resolves the runtime value of a {@see ResponseHeader}.
 *
 * Returning `null` removes the header from the response. Use an empty string to
 * produce an empty header value.
 */
interface ResponseHeaderProviderInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return string|array<int, string>|null
     */
    public function provide(ResponseHeader $header, HttpOperation $operation, array $context = []): string|array|null;
}
