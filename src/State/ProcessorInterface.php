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

/**
 * Process data: send an email, persist to storage, add to queue etc.
 *
 * @experimental
 */
interface ProcessorInterface
{
    /**
     * Whether this state handler supports the class/identifier tuple.
     *
     * @param mixed $data
     */
    public function supports($data, array $identifiers = [], ?string $operationName = null, array $context = []): bool;

    /**
     * Handle the state.
     *
     * @param mixed $data
     */
    public function process($data, array $identifiers = [], ?string $operationName = null, array $context = []);
}
