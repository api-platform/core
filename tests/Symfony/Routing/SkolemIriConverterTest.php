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

namespace ApiPlatform\Tests\Symfony\Routing;

use ApiPlatform\Symfony\Routing\SkolemIriConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\ResetInterface;

class SkolemIriConverterTest extends TestCase
{
    public function testImplementsResetInterface(): void
    {
        $router = $this->createStub(RouterInterface::class);
        $converter = new SkolemIriConverter($router);

        $this->assertInstanceOf(ResetInterface::class, $converter);
    }

    public function testResetClearsObjectHashMap(): void
    {
        $generatedIds = [];
        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturnCallback(static function (string $name, array $params) use (&$generatedIds): string {
            $generatedIds[] = $params['id'];

            return '/.well-known/genid/'.$params['id'];
        });

        $converter = new SkolemIriConverter($router);

        $resource = new \stdClass();
        $converter->getIriFromResource($resource);
        $firstId = $generatedIds[0];

        $converter->getIriFromResource($resource);
        $this->assertSame($firstId, $generatedIds[1]);

        $converter->reset();

        $converter->getIriFromResource($resource);
        $this->assertNotSame($firstId, $generatedIds[2]);
    }

    public function testResetClearsClassHashMap(): void
    {
        $generatedIds = [];
        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturnCallback(static function (string $name, array $params) use (&$generatedIds): string {
            $generatedIds[] = $params['id'];

            return '/.well-known/genid/'.$params['id'];
        });

        $converter = new SkolemIriConverter($router);

        $converter->getIriFromResource(\stdClass::class);
        $firstId = $generatedIds[0];

        $converter->getIriFromResource(\stdClass::class);
        $this->assertSame($firstId, $generatedIds[1]);

        $converter->reset();

        $converter->getIriFromResource(\stdClass::class);
        $this->assertNotSame($firstId, $generatedIds[2]);
    }

    public function testResetAllowsConverterToBeReused(): void
    {
        $generatedIds = [];
        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturnCallback(static function (string $name, array $params) use (&$generatedIds): string {
            $generatedIds[] = $params['id'];

            return '/.well-known/genid/'.$params['id'];
        });

        $converter = new SkolemIriConverter($router);

        // Simulate multiple request cycles
        for ($i = 0; $i < 3; ++$i) {
            $resource = new \stdClass();
            $converter->getIriFromResource($resource);
            $converter->getIriFromResource(\stdClass::class);
            $converter->reset();
        }

        // Each cycle should generate 2 new IDs (object + class), total 6
        $this->assertCount(6, $generatedIds);
        // All IDs should be unique (no stale cache)
        $this->assertCount(6, array_unique($generatedIds));
    }
}
