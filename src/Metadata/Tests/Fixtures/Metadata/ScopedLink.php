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

namespace ApiPlatform\Metadata\Tests\Fixtures\Metadata;

use ApiPlatform\Metadata\Link;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
final class ScopedLink extends Link
{
    public function __construct(string $permission)
    {
        parent::__construct(
            parameterName: 'dummyId',
            securityObjectName: 'dummy',
            security: \sprintf('is_granted("%s", dummy)', $permission),
        );
    }
}
