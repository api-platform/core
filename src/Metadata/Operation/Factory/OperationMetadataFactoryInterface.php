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

namespace ApiPlatform\Metadata\Operation\Factory;

use ApiPlatform\Metadata\Operation;

/**
 * Finds the operation that matches a URI template or an operation name.
 */
interface OperationMetadataFactoryInterface
{
    public function create(string $uriTemplate, array $context = []): ?Operation;
}
