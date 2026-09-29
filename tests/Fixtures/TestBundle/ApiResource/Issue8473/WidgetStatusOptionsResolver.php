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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8473;

use ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface;

final class WidgetStatusOptionsResolver implements QueryItemResolverInterface
{
    public function __invoke(?object $item, array $context): WidgetStatusOptions
    {
        $options = new WidgetStatusOptions();
        $options->options = array_map(static fn (WidgetStatus $case): string => $case->value, WidgetStatus::cases());

        return $options;
    }
}
