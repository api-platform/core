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

namespace ApiPlatform\Core\Tests\Action;

use ApiPlatform\Core\Action\PlaceholderAction;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PlaceholderActionTest extends \PHPUnit_Framework_TestCase
{
    public function testAction()
    {
        $action = new PlaceholderAction();

        $expected = new \stdClass();
        $this->assertEquals($expected, $action($expected));
    }
}
