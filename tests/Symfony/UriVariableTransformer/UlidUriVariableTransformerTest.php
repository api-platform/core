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

namespace ApiPlatform\Tests\Symfony\UriVariableTransformer;

use ApiPlatform\Exception\InvalidUriVariableException;
use ApiPlatform\Symfony\UriVariableTransformer\UlidUriVariableTransformer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

class UlidUriVariableTransformerTest extends TestCase
{
    /** @var UlidUriVariableTransformer */
    private $transformer;

    public function testTransform(): void
    {
        $dateTime = new DateTimeImmutable('2016-06-13T13:25:20.894000+0000');
        $input = '01AN4Z07BY'.'79KA1307SR9X4MV3';

        $ulid = $this->transformer->transform($input, []);
        $this->assertInstanceOf(Ulid::class, $ulid);
        $this->assertEquals($input, (string) $ulid);
        $this->assertEquals($dateTime, $ulid->getDateTime());
    }

    public function testTransformThrows(): void
    {
        $this->expectException(InvalidUriVariableException::class);
        $this->expectExceptionMessage('Invalid ULID: "Api-Platform-Core2.7');
        $this->transformer->transform('Api-Platform-Core2.7', []);
    }

    public function testSupportsTransformation(): void
    {
        $this->assertTrue($this->transformer->supportsTransformation('01AN4Z07BY79KA1307SR9X4MV3', [Ulid::class]));
        $this->assertTrue($this->transformer->supportsTransformation('01AN4Z07BY79KA1307SR9X4MV3', [new Ulid()]));
        $this->assertTrue($this->transformer->supportsTransformation('Api-Platform-Core2.7', [Ulid::class]));
        $this->assertTrue($this->transformer->supportsTransformation('Api-Platform-Core2.7', [new Ulid()]));

        $this->assertFalse($this->transformer->supportsTransformation('01AN4Z07BY79KA1307SR9X4MV3', [Uuid::class]));
        $this->assertFalse($this->transformer->supportsTransformation('01AN4Z07BY79KA1307SR9X4MV3', [Uuid::v4()]));
    }

    protected function setUp(): void
    {
        $this->transformer = new UlidUriVariableTransformer();
    }
}
