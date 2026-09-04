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

namespace ApiPlatform\Metadata\Tests\Exception;

use ApiPlatform\Metadata\Exception\AccessDeniedException;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use PHPUnit\Framework\TestCase;

final class AccessDeniedExceptionTest extends TestCase
{
    public function testKeepsTheDefaultMessageForBackwardCompatibility(): void
    {
        $this->assertSame('', (new AccessDeniedException())->getMessage());
    }

    public function testKeepsTheInternalMessageSeparateFromThePublicDetail(): void
    {
        $exception = new AccessDeniedException('Access Denied. Voter reason.', detail: 'Access Denied.');

        $this->assertInstanceOf(ProblemExceptionInterface::class, $exception);
        $this->assertSame('Access Denied. Voter reason.', $exception->getMessage());
        $this->assertSame('Access Denied.', $exception->getDetail());
    }

    public function testKeepsAMissingPublicDetailNull(): void
    {
        $exception = new AccessDeniedException('Access Denied. Voter reason.');

        $this->assertNull($exception->getDetail());
    }
}
