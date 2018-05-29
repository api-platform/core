<?php
declare(strict_types=1);

namespace ApiPlatform\Core\Tests\PropertyInfo;

use ApiPlatform\Core\PropertyInfo\ConstructorExtractor;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\VoDummyCar;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\VoDummyNoConstructor;
use PHPUnit\Framework\TestCase;

class ConstructorExtractorTest extends TestCase
{
    /**
     * @var ConstructorExtractor
     */
    private $constructorExtractor;

    protected function setUp()
    {
        parent::setUp();
        $this->constructorExtractor = new ConstructorExtractor();
    }

    public function testIsReadable()
    {
        $this->assertNull($this->constructorExtractor->isReadable(VoDummyCar::class, 'mileage'));
    }

    public function testIsWritable()
    {
        $this->assertNull($this->constructorExtractor->isWritable(VoDummyCar::class, 'id'));

        $this->assertNull($this->constructorExtractor->isWritable(VoDummyNoConstructor::class, 'id'));

        $this->assertTrue($this->constructorExtractor->isWritable(VoDummyCar::class, 'bodyType'));
        $this->assertTrue($this->constructorExtractor->isWritable(VoDummyCar::class, 'drivers'));
        $this->assertTrue($this->constructorExtractor->isWritable(VoDummyCar::class, 'drivers'));
        $this->assertTrue($this->constructorExtractor->isWritable(VoDummyCar::class, 'insuranceCompany'));
        $this->assertTrue($this->constructorExtractor->isWritable(VoDummyCar::class, 'make'));
        $this->assertTrue($this->constructorExtractor->isWritable(VoDummyCar::class, 'mileage'));
    }
}
