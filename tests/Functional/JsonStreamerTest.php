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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\JsonStreamResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

class JsonStreamerTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [JsonStreamResource::class];
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        if ('mongodb' === $container->getParameter('kernel.environment')) {
            return;
        }

        $registry = $container->get('doctrine');
        $manager = $registry->getManager();
        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        $classes = [];
        foreach ([JsonStreamResource::class] as $entityClass) {
            $classes[] = $manager->getClassMetadata($entityClass);
        }

        try {
            $schemaTool = new SchemaTool($manager);
            @$schemaTool->createSchema($classes);
        } catch (\Exception $e) {
        }

        for ($i = 0; $i < 10; ++$i) {
            $resource = new JsonStreamResource();
            $resource->title = 'Title '.$i;
            // $resource->createdAt = new \DateTimeImmutable();
            // $resource->publishedAt = new \DateTimeImmutable();
            $resource->views = random_int(1, 1000);
            $resource->rating = random_int(1, 5);
            $resource->isFeatured = (bool) random_int(0, 1);
            $resource->price = number_format((float) random_int(10, 1000) / 100, 2, '.', '');

            $manager->persist($resource);
        }

        $manager->flush();
    }

    protected function tearDown(): void
    {
        $container = static::getContainer();
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();
        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        $classes = [];
        foreach ([JsonStreamResource::class] as $entityClass) {
            $classes[] = $manager->getClassMetadata($entityClass);
        }

        $schemaTool = new SchemaTool($manager);
        @$schemaTool->dropSchema($classes);
        parent::tearDown();
    }

    public function testJsonStreamer(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $buffer = '';
        ob_start(function (string $chunk) use (&$buffer): void {
            $buffer .= $chunk;
        });

        self::createClient()->request('GET', '/json_stream_resources/1', ['headers' => ['accept' => 'application/ld+json']]);

        ob_get_clean();

        $res = json_decode($buffer, true);
        $this->assertIsInt($res['views']);
        $this->assertIsInt($res['rating']);
        $this->assertIsBool($res['isFeatured']);
        $this->assertIsString($res['price']);
        $this->assertEquals('/json_stream_resources/1', $res['@id']);
        $this->assertEquals('/contexts/JsonStreamResource', $res['@context']);
    }

    public function testJsonStreamerCollection(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $buffer = '';
        ob_start(function (string $chunk) use (&$buffer): void {
            $buffer .= $chunk;
        });

        self::createClient()->request('GET', '/json_stream_resources', ['headers' => ['accept' => 'application/ld+json']]);

        ob_get_clean();

        $res = json_decode($buffer, true);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('@context', $res);
        $this->assertArrayHasKey('@id', $res);
        $this->assertArrayHasKey('@type', $res);
        $this->assertEquals('Collection', $res['@type']);
        $this->assertArrayHasKey('member', $res);
        $this->assertIsArray($res['member']);
        $this->assertArrayHasKey('totalItems', $res);
        $this->assertIsInt($res['totalItems']);
    }

    public function testJsonStreamerWrite(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $buffer = '';
        ob_start(function (string $chunk) use (&$buffer): void {
            $buffer .= $chunk;
        });

        self::createClient()->request('POST', '/json_stream_resources', [
            'json' => [
                'title' => 'asd',
                'views' => 0,
                'rating' => 0.0,
                'isFeatured' => false,
                'price' => '0.00',
            ],
        ]);

        ob_get_clean();

        $res = json_decode($buffer, true);

        $this->assertSame('asd', $res['title']);
        $this->assertSame(0, $res['views']);
        $this->assertSame(0, $res['rating']);
        $this->assertFalse($res['isFeatured']);
        $this->assertSame('0', $res['price']);
        $this->assertStringStartsWith('/json_stream_resources/', $res['@id']);
        $this->assertSame('/contexts/JsonStreamResource', $res['@context']);

        $container = static::getContainer();
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();
        $jsonStreamResource = $manager->find(JsonStreamResource::class, $res['id']);
        $this->assertNotNull($jsonStreamResource);
    }
}
