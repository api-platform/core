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
        $idUrl = $context['args']['id'];

        if (str_contains($idUrl, '2')) {
            // Unknown to simulate a 403 error
            return new SecurityAfterResolver('2', 'nonexistent');
        }

        return new SecurityAfterResolver('1', 'test');
    }
}
