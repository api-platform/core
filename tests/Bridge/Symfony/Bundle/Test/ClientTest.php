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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Response;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpKernel\Profiler\Profile;

class ClientTest extends ApiTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        /**
         * @var EntityManagerInterface
         */
        $manager = self::$container->get('doctrine')->getManager();
        $classes = $manager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($manager);

        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }

    public function testRequest(): void
    {
        $client = self::createClient();
        $client->getKernelBrowser();
        $this->assertSame(self::$kernel->getContainer(), $client->getContainer());
        $this->assertSame(self::$kernel, $client->getKernel());

        $client->enableProfiler();
        $response = $client->request('GET', '/');

        $this->assertSame('/contexts/Entrypoint', $response->toArray()['@context']);
        $this->assertInstanceOf(Profile::class, $client->getProfile());

        $this->assertInstanceOf(Response::class, $response);
        $response->getKernelResponse();
        $response->getBrowserKitResponse();
    }

    public function testCustomHeader(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $response = $client->request('POST', '/dummies', [
            'headers' => [
                'content-type' => 'application/json',
                'accept' => 'text/xml',
            ],
            'body' => '{"name": "Kevin"}',
        ]);
        $this->assertSame('application/xml; charset=utf-8', $response->getHeaders()['content-type'][0]);
        $this->assertContains('<name>Kevin</name>', $response->getContent());
    }

    /**
     * @dataProvider authBasicProvider
     */
    public function testAuthBasic($basic): void
    {
        $client = self::createClient();
        $client->enableReboot();
        $response = $client->request('GET', '/secured_dummies', ['auth_basic' => $basic]);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function authBasicProvider(): iterable
    {
        yield ['dunglas:kevin'];
        yield [['dunglas', 'kevin']];
    }

    public function testStream(): void
    {
        $this->expectException(\LogicException::class);

        $client = self::createClient();
        $client->stream([]);
    }
}
