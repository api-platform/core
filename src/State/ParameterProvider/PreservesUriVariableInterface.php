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

namespace ApiPlatform\State\ParameterProvider;

/**
 * Marks a ParameterProviderInterface whose value must not replace the uri variable it was computed from.
 *
 * A provider is otherwise assumed to transform its parameter value, and the result becomes the uri
 * variable the resource is queried with. Implement this interface when the value is something else,
 * typically a resource resolved for a security expression rather than an identifier.
 */
interface PreservesUriVariableInterface
{
}
