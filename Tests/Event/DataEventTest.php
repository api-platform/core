<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class DataEventTest extends \PHPUnit_Framework_TestCase
{
    public function testInitialize()
    {
        $resource = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface')->reveal();
        $data = new \stdClass();

        $dataEvent = new \Dunglas\ApiBundle\Event\DataEvent($resource, $data);
        $this->assertEquals($resource, $dataEvent->getResource());
        $this->assertEquals($data, $dataEvent->getData());
    }
}
