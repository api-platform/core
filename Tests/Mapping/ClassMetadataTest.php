<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Doctrine\Mapping;

use Dunglas\ApiBundle\Mapping\ClassMetadata;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ClassMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialize()
    {
        $classMetadata = new ClassMetadata('test');
        $serialized = serialize($classMetadata);

        $this->assertEquals($classMetadata, unserialize($serialized));
    }
}
