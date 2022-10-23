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

namespace ApiPlatform\Metadata\Extractor;

/**
 * Extracts a dynamic resource (used by GraphQL for nested resources).
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface DynamicResourceExtractorInterface extends ResourceExtractorInterface
{
    public function addResource(string $resourceClass, array $config = []): string;
}
