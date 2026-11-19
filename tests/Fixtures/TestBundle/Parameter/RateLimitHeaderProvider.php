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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterProviderInterface;

final class RateLimitHeaderProvider implements ParameterProviderInterface
{
    public function provide(Parameter $parameter, array $parameters = [], array $context = []): ?Operation
    {
        $value = match ($parameter->getKey()) {
            'RateLimit-Limit' => '100',
            'RateLimit-Remaining' => '99',
            default => null,
        };

        if (null !== $value) {
            $parameter->setValue($value);
        }

        return $context['operation'] ?? null;
    }
}
