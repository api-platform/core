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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6354;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;

final class CreateActivityLogResolver implements MutationResolverInterface
{
    /**
     * @param object|null $item
     * @param mixed[]     $context
     */
    public function __invoke($item, array $context): ActivityLog
    {
        if (!$item instanceof ActivityLog) {
            throw new \InvalidArgumentException('Missing input of type ActivityLog');
        }

        $item->id = 0;
        $item->name = 'hi';

        return $item;
    }
}
