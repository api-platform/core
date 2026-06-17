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

namespace ApiPlatform\Tests\Symfony\Security\Exception;

use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

class AccessDeniedExceptionTest extends TestCase
{
    #[IgnoreDeprecations]
    public function testInstantiationTriggersDeprecation(): void
    {
        $this->expectUserDeprecationMessage('Since api-platform/core 4.4: The "ApiPlatform\Symfony\Security\Exception\AccessDeniedException" class is deprecated, use "ApiPlatform\Metadata\Exception\AccessDeniedException" instead.');

        new AccessDeniedException();
    }

    #[IgnoreDeprecations]
    public function testKeepsBaseExceptionBehavior(): void
    {
        $previous = new \RuntimeException('previous');
        $exception = new AccessDeniedException('Custom message', $previous, 403);

        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame(403, $exception->getStatusCode());
        $this->assertSame([], $exception->getHeaders());
    }
}
