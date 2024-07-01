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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6427;

use ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface;

final class SecurityAfterResolverResolver implements QueryItemResolverInterface
{
    /**
     * @param object|null $item
     * @param mixed[]     $context
     */
    public function __invoke($item, array $context): SecurityAfterResolver
    {
        return new SecurityAfterResolver('1', 'test');
    }
}
