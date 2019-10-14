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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Messenger;

use ApiPlatform\Core\Bridge\Symfony\Messenger\ContextStamp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * @author Sergii Pavlenko <sergii.pavlenko.v@gmail.com>
 */
class ContextStampTest extends TestCase
{
    public function testConstruct()
    {
        $this->assertInstanceOf(StampInterface::class, new ContextStamp());
    }

    public function testGetContext()
    {
        $contextStamp = new ContextStamp();
        $this->assertIsArray($contextStamp->getContext());
    }
}
