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

namespace ApiPlatform\Symfony\Bundle\ArgumentResolver;

use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;

if (interface_exists(ValueResolverInterface::class)) {
    /** @internal */
    interface CompatibleValueResolverInterface extends ValueResolverInterface
    {
    }
} else {
    /** @internal */
    interface CompatibleValueResolverInterface extends ArgumentValueResolverInterface
    {
    }
}
