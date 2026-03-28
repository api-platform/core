<?php

declare(strict_types=1);

namespace ApiPlatform\OpenApi\Tests\Model;

use ApiPlatform\OpenApi\Model\Parameter;
use PHPUnit\Framework\TestCase;

class ParameterTest extends TestCase
{
    public function testExplodeDefaultsTrueForFormStyle(): void
    {
        $parameter = new Parameter('test', 'query');
        $this->assertSame('form', $parameter->getStyle());
        $this->assertTrue($parameter->getExplode());
    }

    public function testExplodeDefaultsTrueForCookieStyle(): void
    {
        $parameter = new Parameter('test', 'cookie');
        $this->assertSame('form', $parameter->getStyle());
        $this->assertTrue($parameter->getExplode());
    }

    public function testExplodeDefaultsFalseForPathStyle(): void
    {
        $parameter = new Parameter('test', 'path');
        $this->assertSame('simple', $parameter->getStyle());
        $this->assertFalse($parameter->getExplode());
    }

    public function testExplodeDefaultsFalseForHeaderStyle(): void
    {
        $parameter = new Parameter('test', 'header');
        $this->assertSame('simple', $parameter->getStyle());
        $this->assertFalse($parameter->getExplode());
    }

    public function testExplicitExplodeFalseOverridesDefault(): void
    {
        $parameter = new Parameter('test', 'query', explode: false);
        $this->assertSame('form', $parameter->getStyle());
        $this->assertFalse($parameter->getExplode());
    }

    public function testExplicitExplodeTrueOnSimpleStyle(): void
    {
        $parameter = new Parameter('test', 'path', explode: true);
        $this->assertSame('simple', $parameter->getStyle());
        $this->assertTrue($parameter->getExplode());
    }

    public function testExplodeDefaultsTrueForExplicitFormStyle(): void
    {
        $parameter = new Parameter('test', 'path', style: 'form');
        $this->assertTrue($parameter->getExplode());
    }

    public function testExplodeDefaultsFalseForExplicitDeepObjectStyle(): void
    {
        $parameter = new Parameter('test', 'query', style: 'deepObject');
        $this->assertFalse($parameter->getExplode());
    }
}
