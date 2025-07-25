<?php

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

        for ($i = 0; $i < 1000; ++$i) {
            $resource = new JsonStreamResource();
            $resource->title = 'Title ' . $i;
            // $resource->createdAt = new \DateTimeImmutable();
            // $resource->publishedAt = new \DateTimeImmutable();
            $resource->views = rand(1, 1000);
            $resource->rating = rand(1, 5);
            $resource->isFeatured = (bool) rand(0, 1);
            $resource->price = number_format((float) rand(10, 1000) / 100, 2, '.', '');

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
        dump($res);
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
        dump($res);
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
        dump($res);
    }
}
