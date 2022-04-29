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

namespace ApiPlatform\Tests\Symfony\Bundle\Twig;

use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DocumentDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 * @group legacy
 */
class ApiPlatformProfilerPanelTest extends WebTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $manager;
    private $schemaTool;
    private $env;
    private $legacy;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->env = $kernel->getEnvironment();
        $this->legacy = $kernel->getContainer()->getParameter('api_platform.metadata_backward_compatibility_layer');

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');
        /** @var EntityManagerInterface $manager */
        $manager = $doctrine->getManager();
        $this->manager = $manager;
        $this->schemaTool = new SchemaTool($this->manager);
        /** @var \Doctrine\ORM\Mapping\ClassMetadata[] $classes */
        $classes = $this->manager->getMetadataFactory()->getAllMetadata();
        $this->schemaTool->dropSchema($classes);
        $this->manager->clear();
        $this->schemaTool->createSchema($classes);

        $this->ensureKernelShutdown();
    }

    protected function tearDown(): void
    {
        $this->schemaTool->dropSchema($this->manager->getMetadataFactory()->getAllMetadata());
        $this->manager->clear();
        parent::tearDown();
    }

    public function testDebugBarContentNotResourceClass()
    {
        if ($this->legacy) {
            $this->markTestSkipped('Legacy test.');

            return;
        }

        $client = static::createClient();
        $client->enableProfiler();
        // Using html to get default Swagger UI
        $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        /** @var string $token */
        $token = $client->getResponse()->headers->get('X-Debug-Token');
        $crawler = $client->request('GET', "/_wdt/$token");

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $block = $crawler->filter('div[class*=sf-toolbar-block-api_platform]');

        // Check extra info content
        $this->assertStringContainsString('sf-toolbar-status-default', $block->attr('class'), 'The toolbar block should have the default color.');
        $this->assertSame('Not an API Platform resource', $block->filterXPath('//div[@class="sf-toolbar-info-piece"][./b[contains(., "Resource Class")]]/span')->html());
    }

    public function testDebugBarContent()
    {
        if ($this->legacy) {
            $this->markTestSkipped('Legacy test.');

            return;
        }

        $client = static::createClient();
        $client->enableProfiler();
        $client->request('GET', '/dummies', [], [], ['HTTP_ACCEPT' => 'application/ld+json']);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        /** @var string $token */
        $token = $client->getResponse()->headers->get('X-Debug-Token');

        $crawler = $client->request('GET', "/_wdt/$token");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $block = $crawler->filter('div[class*=sf-toolbar-block-api_platform]');

        // Check extra info content
        $this->assertStringContainsString('sf-toolbar-status-default', $block->attr('class'), 'The toolbar block should have the default color.');
        $this->assertSame('mongodb' === $this->env ? DocumentDummy::class : Dummy::class, $block->filterXPath('//div[@class="sf-toolbar-info-piece"][./b[contains(., "Resource Class")]]/span')->html());
    }

    public function testProfilerGeneralLayoutNotResourceClass()
    {
        if ($this->legacy) {
            $this->markTestSkipped('Legacy test.');

            return;
        }

        $client = static::createClient();
        $client->enableProfiler();
        // Using html to get default Swagger UI
        $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $crawler = $client->request('GET', '/_profiler/latest?panel=api_platform.data_collector.request', [], [], []);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Check that the Api-Platform sidebar link is active
        $this->assertNotEmpty($menuLink = $crawler->filter('a[href$="panel=api_platform.data_collector.request"]'));
        $this->assertNotEmpty($menuLink->filter('.disabled'), 'The sidebar menu should be disabled.');

        $metrics = $crawler->filter('.metrics');
        $this->assertCount(1, $metrics->filter('.metric'), 'The should be one metric displayed (resource class).');
        $this->assertSame('Not an API Platform resource', $metrics->filter('span.value')->html());

        $this->assertEmpty($crawler->filter('.sf-tabs .tab'), 'Tabs must not be presents on the panel.');
    }

    public function testProfilerGeneralLayout()
    {
        if ($this->legacy) {
            $this->markTestSkipped('Legacy test.');

            return;
        }

        $client = static::createClient();
        $client->enableProfiler();
        $client->request('GET', '/dummies', [], [], ['HTTP_ACCEPT' => 'application/ld+json']);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $crawler = $client->request('GET', '/_profiler/latest?panel=api_platform.data_collector.request', [], [], []);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Check that the Api-Platform sidebar link is active
        $this->assertNotEmpty($menuLink = $crawler->filter('a[href$="panel=api_platform.data_collector.request"]'));
        $this->assertEmpty($menuLink->filter('.disabled'), 'The sidebar menu should not be disabled.');

        $metrics = $crawler->filter('.metrics');
        $this->assertCount(1, $metrics->filter('.metric'), 'The should be one metric displayed (resource class).');
        $this->assertSame('mongodb' === $this->env ? DocumentDummy::class : Dummy::class, $metrics->filter('span.value')->html());

        $this->assertCount(6, $crawler->filter('.sf-tabs .tab-content'), 'Tabs must be presents on the panel.');

        // Metadata tab
        $this->assertSame('Metadata', $crawler->filter('.tab:nth-of-type(1) .tab-title')->html());
        $tabContent = $crawler->filter('.tab:nth-of-type(1) .tab-content');
        $this->assertStringEndsWith('Dummy', trim($tabContent->filter('h3')->html()), 'the resource shortname should be displayed.');

        $this->assertCount(9, $tabContent->filter('table'));
        $this->assertSame('Resource', $tabContent->filter('table:first-of-type thead th:first-of-type')->html());
        $this->assertSame('Operations', $tabContent->filter('table:nth-of-type(2) thead th:first-of-type')->html());
        $this->assertSame('Filters', $tabContent->filter('table:nth-of-type(3) thead th:first-of-type')->html());

        // Data providers tab
        $this->assertSame('Data Providers', $crawler->filter('.data-provider-tab-title')->html());
        $this->assertNotEmpty($crawler->filter('.data-provider-tab-content'));

        // Data persisters tab
        $this->assertSame('Data Persisters', $crawler->filter('.data-persister-tab-title')->html());
        $this->assertNotEmpty($crawler->filter('.data-persister-tab-content'));
    }
}
