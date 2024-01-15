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
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes data: sends an email, persists to storage, adds to queue etc.
 *
 * @template T1
 * @template T2
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
interface ProcessorInterface
{
    /**
     * Handles the state.
     *
     * @param T1                                                                                                                   $data
     * @param array<string, mixed>                                                                                                 $uriVariables
     * @param array<string, mixed>&array{request?: Request, previous_data?: mixed, resource_class?: string, original_data?: mixed} $context
     *
     * @return T2
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []);
}
