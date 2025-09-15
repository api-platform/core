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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\AggregateRating;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Product;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\JsonStreamResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerHelper;
use Symfony\Component\JsonStreamer\JsonStreamWriter;

class JsonStreamerTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [JsonStreamResource::class, Product::class, AggregateRating::class];
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
            $resource->createdAt = new \DateTimeImmutable();
            $resource->publishedAt = new \DateTimeImmutable();
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

    public function testJsonStreamerJsonLd(): void
    {
        $container = static::getContainer();
        if (false === (class_exists(ControllerHelper::class) && class_exists(JsonStreamWriter::class))) {
            $this->markTestSkipped('JsonStreamer component not installed.');
        }

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
        $this->assertEquals('JsonStreamResource', $res['@type']);
        $this->assertEquals('/contexts/JsonStreamResource', $res['@context']);
    }

    public function testJsonStreamerCollectionJsonLd(): void
    {
        if (false === (class_exists(ControllerHelper::class) && class_exists(JsonStreamWriter::class))) {
            $this->markTestSkipped('JsonStreamer component not installed.');
        }
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
        $this->assertEquals('JsonStreamResource', $res['member'][0]['@type']);
        $this->assertArrayHasKey('totalItems', $res);
        $this->assertIsInt($res['totalItems']);
    }

    public function testJsonStreamerJson(): void
    {
        if (false === (class_exists(ControllerHelper::class) && class_exists(JsonStreamWriter::class))) {
            $this->markTestSkipped('JsonStreamer component not installed.');
        }
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $buffer = '';
        ob_start(function (string $chunk) use (&$buffer): void {
            $buffer .= $chunk;
        });

        self::createClient()->request('GET', '/json_stream_resources/1', ['headers' => ['accept' => 'application/json']]);

        ob_get_clean();

        $res = json_decode($buffer, true);
        $this->assertIsInt($res['views']);
        $this->assertIsInt($res['rating']);
        $this->assertIsBool($res['isFeatured']);
        $this->assertIsString($res['price']);
        $this->assertArrayNotHasKey('@id', $res);
        $this->assertArrayNotHasKey('@type', $res);
        $this->assertArrayNotHasKey('@context', $res);
    }

    public function testJsonStreamerCollectionJson(): void
    {
        if (false === (class_exists(ControllerHelper::class) && class_exists(JsonStreamWriter::class))) {
            $this->markTestSkipped('JsonStreamer component not installed.');
        }
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $buffer = '';
        ob_start(function (string $chunk) use (&$buffer): void {
            $buffer .= $chunk;
        });

        self::createClient()->request('GET', '/json_stream_resources', ['headers' => ['accept' => 'application/json']]);

        ob_get_clean();

        $res = json_decode($buffer, true);

        $this->assertIsArray($res);
        $this->assertArrayNotHasKey('@id', $res);
        $this->assertArrayNotHasKey('@type', $res);
        $this->assertArrayNotHasKey('@context', $res);
    }

    public function testJsonStreamerWriteJsonLd(): void
    {
        if (false === (class_exists(ControllerHelper::class) && class_exists(JsonStreamWriter::class))) {
            $this->markTestSkipped('JsonStreamer component not installed.');
        }
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        if (\PHP_VERSION_ID < 80400) {
            $this->markTestSkipped('PHP version is lower than 8.4');
        }

        $buffer = '';
        ob_start(function (string $chunk) use (&$buffer): void {
            $buffer .= $chunk;
        });

        self::createClient()->request('POST', '/json_stream_resources', [
            'json' => [
                'title' => 'asd',
                'views' => 0,
                'createdAt' => '2024-01-01T12:00:00+00:00',
                'publishedAt' => '2024-01-01T12:00:00+00:00',
                'rating' => 0.0,
                'isFeatured' => false,
                'price' => '0.00',
            ],
            'headers' => ['content-type' => 'application/ld+json'],
        ]);

        ob_get_clean();

        $res = json_decode($buffer, true);

        $this->assertResponseIsSuccessful();
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

    public function testJsonStreamerWriteJson(): void
    {
        if (false === (class_exists(ControllerHelper::class) && class_exists(JsonStreamWriter::class))) {
            $this->markTestSkipped('JsonStreamer component not installed.');
        }

        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        if (\PHP_VERSION_ID < 80400) {
            $this->markTestSkipped('PHP version is lower than 8.4');
        }

        $buffer = '';
        ob_start(function (string $chunk) use (&$buffer): void {
            $buffer .= $chunk;
        });

        self::createClient()->request('POST', '/json_stream_resources', [
            'json' => [
                'title' => 'asd',
                'views' => 0,
                'createdAt' => '2024-01-01T12:00:00+00:00',
                'publishedAt' => '2024-01-01T12:00:00+00:00',
                'rating' => 0.0,
                'isFeatured' => false,
                'price' => '0.00',
            ],
            'headers' => ['content-type' => 'application/json', 'accept' => 'application/json'],
        ]);

        ob_get_clean();

        $res = json_decode($buffer, true);

        $this->assertResponseIsSuccessful();
        $this->assertSame('asd', $res['title']);
        $this->assertSame(0, $res['views']);
        $this->assertSame(0, $res['rating']);
        $this->assertFalse($res['isFeatured']);
        $this->assertSame('0', $res['price']);
        $this->assertArrayNotHasKey('@id', $res);
        $this->assertArrayNotHasKey('@type', $res);
        $this->assertArrayNotHasKey('@context', $res);

        $container = static::getContainer();
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();
        $jsonStreamResource = $manager->find(JsonStreamResource::class, $res['id']);
        $this->assertNotNull($jsonStreamResource);
    }

    public function testJsonStreamerJsonLdGenIdFalseWithDifferentTypeThenShortname(): void
    {
        if (false === (class_exists(ControllerHelper::class) && class_exists(JsonStreamWriter::class))) {
            $this->markTestSkipped('JsonStreamer component not installed.');
        }
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $buffer = '';
        ob_start(function (string $chunk) use (&$buffer): void {
            $buffer .= $chunk;
        });

        self::createClient()->request('GET', '/json-stream-products/test', ['headers' => ['accept' => 'application/ld+json']]);

        ob_get_clean();

        $res = json_decode($buffer, true);
        $this->assertArrayNotHasKey('@id', $res['aggregateRating']);
        $this->assertEquals('https://schema.org/AggregateRating', $res['aggregateRating']['@type']);
        $this->assertEquals('https://schema.org/Product', $res['@type']);
    }
}
