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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8115;

use Symfony\Component\Serializer\Attribute\Groups;

class Module
{
    public function __construct(
        #[Groups(['module'])]
        public readonly string $name = '',
        public readonly string $secret = '',
    ) {
    }
}
