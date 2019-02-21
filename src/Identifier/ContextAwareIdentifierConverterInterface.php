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

namespace ApiPlatform\Core\Identifier;

/**
 * Identifier converter.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */

namespace ApiPlatform\Core\Identifier;

/**
 * Gives access to the context.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ContextAwareIdentifierConverterInterface extends IdentifierConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert(string $data, string $class, array $context = []): array;
}
