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

namespace ApiPlatform\Tests\Symfony\EventListener;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class EventPrioritiesTest extends TestCase
{
    public function testConstants(): void
    {
        $this->assertSame(5, EventPriorities::PRE_READ);
        $this->assertSame(3, EventPriorities::POST_READ);
        $this->assertSame(3, EventPriorities::PRE_DESERIALIZE);
        $this->assertSame(1, EventPriorities::POST_DESERIALIZE);
        $this->assertSame(65, EventPriorities::PRE_VALIDATE);
        $this->assertSame(63, EventPriorities::POST_VALIDATE);
        $this->assertSame(33, EventPriorities::PRE_WRITE);
        $this->assertSame(31, EventPriorities::POST_WRITE);
        $this->assertSame(17, EventPriorities::PRE_SERIALIZE);
        $this->assertSame(15, EventPriorities::POST_SERIALIZE);
        $this->assertSame(9, EventPriorities::PRE_RESPOND);
        $this->assertSame(0, EventPriorities::POST_RESPOND);
    }
}
