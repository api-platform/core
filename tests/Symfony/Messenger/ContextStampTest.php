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

namespace ApiPlatform\Tests\Symfony\Messenger;

use ApiPlatform\Symfony\Messenger\ContextStamp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * @author Sergii Pavlenko <sergii.pavlenko.v@gmail.com>
 */
class ContextStampTest extends TestCase
{
    public function testConstruct(): void
    {
        $this->assertInstanceOf(StampInterface::class, new ContextStamp());
    }

    public function testGetContext(): void
    {
        $contextStamp = new ContextStamp();
        $this->assertIsArray($contextStamp->getContext());
    }

    public function testRequestIsUnset(): void
    {
        $request = new Request();
        $stamp = new ContextStamp(['request' => $request]);

        self::assertArrayNotHasKey('request', $stamp->getContext());
    }
}
