<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Action;

use ApiPlatform\Core\Action\NullAction;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class NullActionTest extends \PHPUnit_Framework_TestCase
{
    public function testNullAction()
    {
        $action = new NullAction();
        $this->assertNull($action());
    }
}
