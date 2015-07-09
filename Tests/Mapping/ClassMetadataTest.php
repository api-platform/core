<?php

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
