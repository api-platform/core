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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Parameter;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\ResponseHeader;
use ApiPlatform\State\ResponseHeaderProviderInterface;

final class RateLimitHeaderProvider implements ResponseHeaderProviderInterface
{
    public function provide(ResponseHeader $header, HttpOperation $operation, array $context = []): string|array|null
    {
        return match ($header->getKey()) {
            'RateLimit-Limit' => '100',
            'RateLimit-Remaining' => '99',
            default => null,
        };
    }
}
