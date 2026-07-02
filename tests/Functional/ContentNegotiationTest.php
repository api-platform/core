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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCustomFormat;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SecuredDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ContentNegotiationTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Dummy::class, DummyCustomFormat::class, SecuredDummy::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([Dummy::class, DummyCustomFormat::class, SecuredDummy::class]);
    }

    private function createDummyViaXml(): void
    {
        self::createClient()->request('POST', '/dummies', [
            'headers' => ['Accept' => 'application/xml', 'Content-Type' => 'application/xml'],
            'body' => "<root>\n    <name>XML!</name>\n</root>",
        ]);
    }

    public function testPostXmlBody(): void
    {
        $response = self::createClient()->request('POST', '/dummies', [
            'headers' => ['Accept' => 'application/xml', 'Content-Type' => 'application/xml'],
            'body' => "<root>\n    <name>XML!</name>\n</root>",
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');
        $this->assertStringContainsString('<name>XML!</name>', $response->getContent());
        $this->assertStringContainsString('<id>1</id>', $response->getContent());
    }

    public function testRetrieveCollectionInXml(): void
    {
        $this->createDummyViaXml();

        $response = self::createClient()->request('GET', '/dummies', [
            'headers' => ['Accept' => 'text/xml'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');
        $this->assertStringContainsString('<item key="0">', $response->getContent());
        $this->assertStringContainsString('<name>XML!</name>', $response->getContent());
    }

    public function testRetrieveCollectionInXmlViaUrlSuffix(): void
    {
        $this->createDummyViaXml();

        $response = self::createClient()->request('GET', '/dummies.xml', [
            'headers' => ['Accept' => '*/*'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');
        $this->assertStringContainsString('<name>XML!</name>', $response->getContent());
    }

    public function testRetrieveCollectionInJson(): void
    {
        $this->createDummyViaXml();

        $response = self::createClient()->request('GET', '/dummies', [
            'headers' => ['Accept' => 'application/json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $data = $response->toArray();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertSame('XML!', $data[0]['name']);
        $this->assertSame(1, $data[0]['id']);
    }

    public function testPostJsonAcceptXml(): void
    {
        $this->createDummyViaXml();

        $response = self::createClient()->request('POST', '/dummies', [
            'headers' => ['Accept' => 'application/xml', 'Content-Type' => 'application/json'],
            'json' => ['name' => 'Sent in JSON'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');
        $this->assertStringContainsString('<name>Sent in JSON</name>', $response->getContent());
        $this->assertStringContainsString('<id>2</id>', $response->getContent());
    }

    public function testFormatNegotiatedViaUrlMatchesAccept(): void
    {
        $this->createDummyViaXml();

        self::createClient()->request('GET', '/dummies/1.xml', [
            'headers' => ['Accept' => 'text/xml'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');
    }

    public function testWildcardAcceptDefaultsToFirstFormat(): void
    {
        $this->createDummyViaXml();

        self::createClient()->request('GET', '/dummies/1', [
            'headers' => ['Accept' => '*/*'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
    }

    public function testWildcardAcceptDefaultsToUrlFormat(): void
    {
        $this->createDummyViaXml();

        self::createClient()->request('GET', '/dummies/1.xml', [
            'headers' => ['Accept' => 'text/plain; charset=utf-8, */*'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');
    }

    public function testUnknownFormatReturns406(): void
    {
        $this->createDummyViaXml();

        self::createClient()->request('GET', '/dummies/1', [
            'headers' => ['Accept' => 'text/plain'],
        ]);

        $this->assertResponseStatusCodeSame(406);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    public function testHtmlAcceptReturnsHtmlError(): void
    {
        $response = self::createClient()->request('GET', '/dummies/666', [
            'headers' => ['Accept' => 'text/html'],
        ]);

        $this->assertResponseStatusCodeSame(404);
        $contentType = $response->getHeaders(false)['content-type'][0] ?? '';
        $this->assertStringStartsWith('text/html', $contentType);
    }

    public function testRemovedFormatReturns406(): void
    {
        self::createClient()->request('GET', '/dummy_custom_formats', [
            'headers' => ['Accept' => 'application/json'],
        ]);

        $this->assertResponseStatusCodeSame(406);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    public function testPostCsvBodyOnCustomFormatResource(): void
    {
        $response = self::createClient()->request('POST', '/dummy_custom_formats', [
            'headers' => ['Accept' => 'application/xml', 'Content-Type' => 'text/csv'],
            'body' => "name\nKevin\n",
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');
        $this->assertStringContainsString('<name>Kevin</name>', $response->getContent());
        $this->assertStringContainsString('<id>1</id>', $response->getContent());
    }

    public function testRetrieveCollectionInCsv(): void
    {
        self::createClient()->request('POST', '/dummy_custom_formats', [
            'headers' => ['Accept' => 'application/xml', 'Content-Type' => 'text/csv'],
            'body' => "name\nKevin\n",
        ]);

        $response = self::createClient()->request('GET', '/dummy_custom_formats', [
            'headers' => ['Accept' => 'text/csv'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('id,name', $response->getContent());
        $this->assertStringContainsString('1,Kevin', $response->getContent());
    }

    public function testSecurityErrorInJson(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('SecuredDummy seed uses ORM Entity class.');
        }

        $manager = $this->getManager();
        $securedDummy = new SecuredDummy();
        $securedDummy->setTitle('#1');
        $securedDummy->setDescription('Hello #1');
        $securedDummy->setOwner('notexist');
        $manager->persist($securedDummy);
        $manager->flush();

        self::createClient()->request('GET', '/secured_dummies', [
            'headers' => ['Accept' => 'application/json'],
        ]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertJsonEquals(['message' => 'Authentication Required']);
    }
}
