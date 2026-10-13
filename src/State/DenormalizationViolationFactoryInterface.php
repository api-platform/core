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
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;

/**
 * Promotes Symfony serializer denormalization errors (raw type mismatches that would
 * otherwise produce a 400) into HTTP-level validation violations (422) when the target
 * {@see Operation} declares a matching validation contract.
 *
 * Each framework integration provides its own implementation: the Symfony bundle reads
 * Symfony Validator metadata and throws {@see \ApiPlatform\Validator\Exception\ValidationException};
 * the Laravel package reads Illuminate validation rules and throws Laravel's native
 * {@see \ApiPlatform\Laravel\ApiResource\ValidationError}. Implementations must NOT
 * depend on a sibling framework's validation stack.
 *
 * Contract: throw an HTTP exception (typically 422) when at least one error has a
 * matching validation contract; return void when nothing matches so the caller can
 * rethrow the original denormalization exception for an honest 400.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @see https://github.com/api-platform/core/issues/7981
 */
interface DenormalizationViolationFactoryInterface
{
    /**
     * Builds and throws a validation violation from a denormalization error.
     *
     * Accepts either a single {@see NotNormalizableValueException} (raised when the
     * serializer fails on the first type mismatch) or a {@see PartialDenormalizationException}
     * (raised when `collect_denormalization_errors=true` collects every type mismatch in
     * a batch). Implementations dispatch on the concrete type.
     *
     * @throws \Throwable when at least one error has a matching validation contract
     */
    public function handle(NotNormalizableValueException|PartialDenormalizationException $exception, Operation $operation): void;
}
