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

namespace ApiPlatform\Core\Serializer;

/**
 * Creates and manipulates the Serializer context.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait ContextTrait
{
    /**
     * Initializes the context.
     *
     * @param string $resourceClass
     * @param array  $context
     *
     * @return array
     */
    private function initContext(string $resourceClass, array $context): array
    {
        if (isset($context['api_sub_level'])) {
            return $context;
        }

        return array_merge($context, [
            'api_sub_level' => true,
            'resource_class' => $resourceClass,
        ]);
    }
}
