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

namespace ApiPlatform\JsonSchema;

use ApiPlatform\Metadata\Operation;

/**
 * Factory for creating definition names for resources in a JSON Schema document.
 *
 * @author Gwendolen Lynch <gwendolen.lynch@gmail.com>
 */
interface DefinitionNameFactoryInterface
{
    /**
     * Creates a resource definition name.
     *
     * @param class-string $className
     *
     * @return string the definition name
     */
    public function create(string $className, string $format = 'json', ?string $inputOrOutputClass = null, ?Operation $operation = null, array $serializerContext = []): string;
}
