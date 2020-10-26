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
use PHPUnit\Runner\Version;
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
        $this->assertResponseHeaderSame('content-type', 'application/xml; charset=utf-8');
        $this->assertStringContainsString('<name>Kevin</name>', $response->getContent());
    }

    public function testDefaultHeaders(): void
    {
        $client = self::createClient([], [
            'headers' => [
                'content-type' => 'application/json',
                'accept' => 'text/xml',
            ],
        ]);
        $client->disableReboot();

        $response = $client->request('POST', '/dummies', [
            'body' => '{"name": "Kevin"}',
        ]);

        $this->assertSame('application/xml; charset=utf-8', $response->getHeaders()['content-type'][0]);
        $this->assertResponseHeaderSame('content-type', 'application/xml; charset=utf-8');
        $this->assertStringContainsString('<name>Kevin</name>', $response->getContent());
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
        $this->assertResponseIsSuccessful();
    }

    public function authBasicProvider(): iterable
    {
        yield ['dunglas:kevin'];
        yield [['dunglas', 'kevin']];
    }

    public function testComplexScenario(): void
    {
        if (version_compare(Version::id(), '8.0.0', '<')) {
            $this->markTestSkipped('Requires PHPUnit 8');
        }

        self::createClient()->request('GET', '/secured_dummies', ['auth_basic' => ['dunglas', 'kevin']]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonEquals([
            '@context' => '/contexts/SecuredDummy',
            '@id' => '/secured_dummies',
            '@type' => 'hydra:Collection',
            'hydra:member' => [],
            'hydra:totalItems' => 0,
        ]);

        $this->assertJsonContains(
            [
                '@context' => '/contexts/SecuredDummy',
                '@id' => '/secured_dummies',
            ]
        );

        $this->assertMatchesJsonSchema(<<<JSON
{
  "type": "object",
  "properties": {
    "@context": {"pattern": "^/contexts/SecuredDummy$"},
    "@id": {"pattern": "^/secured_dummies$"},
    "@type": {"pattern": "^hydra:Collection"},
    "hydra:member": {}
  },
  "additionalProperties": true,
  "required": ["@context", "@id", "@type", "hydra:member"]
}
JSON
        );
    }

    public function testStream(): void
    {
        $this->expectException(\LogicException::class);

        $client = self::createClient();
        $client->stream([]);
    }
}
