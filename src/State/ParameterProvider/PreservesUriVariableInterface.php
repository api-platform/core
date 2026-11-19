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

use ApiPlatform\Metadata\Parameter;

/**
 * Implemented by a ParameterProviderInterface whose value is not always the uri variable it was computed
 * from, typically because it resolves a resource for a security expression instead of an identifier.
 *
 * A provider that does not implement this interface is assumed to transform its parameter value, and the
 * result becomes the uri variable the resource is queried with.
 */
interface PreservesUriVariableInterface
{
    /**
     * Whether the uri variable this parameter was computed from must be left untouched.
     */
    public function preservesUriVariable(Parameter $parameter): bool;
}
