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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Dto\Issue5626;

use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5626\Greeting;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * DTO for issue #5626 - contains a nested resource property.
 *
 * This DTO wraps the Greeting resource with additional data (viewCount).
 * The bug is that the schema for the $greeting property incorrectly
 * references GreetingOverviewDto instead of Greeting, causing a self-referencing loop.
 */
class GreetingOverviewDto
{
    public function __construct(
        #[Groups(['Advanced'])]
        public Greeting $greeting,

        #[Groups(['Advanced'])]
        public int $viewCount,
    ) {
    }
}
