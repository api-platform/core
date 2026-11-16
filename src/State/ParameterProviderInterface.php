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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Optionnaly transforms request parameters and provides modification to the current Operation.
 *
 * Parameters are immutable: return `$parameter->withValue($value)` to only change the value, or an Operation
 * holding the replaced parameter.
 */
interface ParameterProviderInterface
{
    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $context
     *
     * @phpstan-param array<string, mixed> $context
     *
     * @psalm-param array{request?: Request, resource_class?: string, operation: Operation, ...<string, mixed>} $context
     */
    public function provide(Parameter $parameter, array $parameters = [], array $context = []): Operation|Parameter|null;
}
