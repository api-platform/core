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

namespace ApiPlatform\Symfony\Tests\Action;

use ApiPlatform\Symfony\Action\PlaceholderAction;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PlaceholderActionTest extends TestCase
{
    public function testAction(): void
    {
        $action = new PlaceholderAction();

        $expected = new \stdClass();
        $this->assertSame($expected, $action($expected));
    }
}
