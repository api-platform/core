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

namespace ApiPlatform\Core\Api;

/**
 * Context aware filter.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
interface ContextAwareFilterInterface extends FilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass, array $context = []): array;
}
