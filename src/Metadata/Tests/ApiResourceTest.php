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

namespace ApiPlatform\Metadata\Tests;

use ApiPlatform\Metadata\ApiResource;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

final class ApiResourceTest extends TestCase
{
    #[IgnoreDeprecations]
    public function testCompositeIdentifierIsDeprecated(): void
    {
        $this->expectUserDeprecationMessage('Since api-platform/core 5.1: The "compositeIdentifier" argument of "ApiPlatform\Metadata\ApiResource" has no effect, it is deprecated and will be removed in 6.0. Set "compositeIdentifier" on a "ApiPlatform\Metadata\Link" instead.');

        new ApiResource(compositeIdentifier: false);
    }
}
