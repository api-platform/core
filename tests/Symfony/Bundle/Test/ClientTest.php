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

namespace ApiPlatform\Tests\Symfony\Bundle\Test;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Response;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwnedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SecuredDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\Security\Core\User\InMemoryUser;

class ClientTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            Dummy::class,
            RelatedDummy::class,
            RelatedOwnedDummy::class,
            SecuredDummy::class,
        ];
    }

    public function testRequest(): void
    {
        $client = self::createClient();
        $client->getKernelBrowser();
        $this->assertSame(static::$kernel, $client->getKernel());

        $client->enableProfiler();
        $response = $client->request('GET', '/');

        $this->assertSame('/contexts/Entrypoint', $response->toArray()['@context']);
        $this->assertInstanceOf(Profile::class, $client->getProfile());
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCustomHeader(): void
    {
        $this->recreateSchema([Dummy::class, RelatedOwnedDummy::class, RelatedDummy::class]);
        $client = self::createClient();
        $client->disableReboot();
        $response = $client->request('POST', '/dummies', [
            'headers' => [
                'content-type' => 'application/json',
                'remote-addr' => '10.10.10.10',
                'accept' => 'text/xml',
            ],
            'body' => '{"name": "Kevin"}',
        ]);
        $server = $client->getKernelBrowser()->getInternalRequest()->getServer();
        $this->assertSame('application/json', $server['CONTENT_TYPE']);
        $this->assertSame('10.10.10.10', $server['REMOTE_ADDR']);
        $this->assertSame('text/xml', $server['HTTP_ACCEPT']);
        $this->assertSame('application/xml; charset=utf-8', $response->getHeaders()['content-type'][0]);
        $this->assertResponseHeaderSame('content-type', 'application/xml; charset=utf-8');
        $this->assertStringContainsString('<name>Kevin</name>', $response->getContent());
    }

    public function testDefaultHeaders(): void
    {
        $this->recreateSchema([Dummy::class, RelatedOwnedDummy::class, RelatedDummy::class]);
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

    #[\PHPUnit\Framework\Attributes\DataProvider('authBasicProvider')]
    public function testAuthBasic($basic): void
    {
        $this->recreateSchema([SecuredDummy::class, RelatedDummy::class]);
        $client = self::createClient();
        $client->enableReboot();
        $response = $client->request('GET', '/secured_dummies', ['auth_basic' => $basic]);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertResponseIsSuccessful();
    }

    public static function authBasicProvider(): iterable
    {
        yield ['dunglas:kevin'];
        yield [['dunglas', 'kevin']];
    }

    public function testComplexScenario(): void
    {
        $this->recreateSchema([SecuredDummy::class, RelatedDummy::class]);
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

    public function testLoginUser(): void
    {
        $this->recreateSchema([SecuredDummy::class, RelatedDummy::class]);
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));

        $client->request('GET', '/secured_dummies');
        $this->assertResponseIsSuccessful();
    }
}
