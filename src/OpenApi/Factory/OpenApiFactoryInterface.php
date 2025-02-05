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

namespace ApiPlatform\OpenApi\Factory;

use ApiPlatform\OpenApi\OpenApi;

interface OpenApiFactoryInterface
{
    /**
     * Creates an OpenApi class.
     *
     * @param array<string, mixed> $context
     */
    public function __invoke(array $context = []): OpenApi;
}
