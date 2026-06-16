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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue2754;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;

final class SumResolver implements MutationResolverInterface
{
    /**
     * @param object|null $item
     * @param mixed[]     $context
     */
    public function __invoke($item, array $context): SumResult
    {
        $input = $context['args']['input'] ?? [];

        return new SumResult(1, ($input['operandA'] ?? 0) + ($input['operandB'] ?? 0));
    }
}
