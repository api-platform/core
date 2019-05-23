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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\Twig;

use ApiPlatform\Core\Bridge\Doctrine\Common\DataPersister;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\CollectionDataProvider as OdmCollectionDataProvider;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\ItemDataProvider as OdmItemDataProvider;
use ApiPlatform\Core\Bridge\Doctrine\Orm\CollectionDataProvider;
use ApiPlatform\Core\Bridge\Doctrine\Orm\ItemDataProvider;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\DataProvider\ContainNonResourceItemDataProvider;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy as DocumentDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
class ApiPlatformProfilerPanelTest extends WebTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $manager;
    private $schemaTool;
    private $env;

    protected function setUp()
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->env = $kernel->getEnvironment();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');
        /** @var EntityManagerInterface $manager */
        $manager = $doctrine->getManager();
        $this->manager = $manager;
        $this->schemaTool = new SchemaTool($this->manager);
        $classes = $this->manager->getMetadataFactory()->getAllMetadata();
        $this->schemaTool->dropSchema($classes);
        $this->manager->clear();
        $this->schemaTool->createSchema($classes);
    }

    protected function tearDown()
    {
        $this->schemaTool->dropSchema($this->manager->getMetadataFactory()->getAllMetadata());
        $this->manager->clear();
        parent::tearDown();
    }

    public function testDebugBarContentNotResourceClass()
    {
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
        $this->assertContains('sf-toolbar-status-default', $block->attr('class'), 'The toolbar block should have the default color.');
        $this->assertSame('Not an API Platform resource', $block->filter('.sf-toolbar-info-piece span')->html());
    }

    public function testDebugBarContent()
    {
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
        $this->assertContains('sf-toolbar-status-default', $block->attr('class'), 'The toolbar block should have the default color.');
        $this->assertSame('mongodb' === $this->env ? DocumentDummy::class : Dummy::class, $block->filter('.sf-toolbar-info-piece span')->html());
    }

    public function testProfilerGeneralLayoutNotResourceClass()
    {
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

        $this->assertCount(3, $crawler->filter('.sf-tabs .tab'), 'Tabs must be presents on the panel.');

        // Metadata tab
        $this->assertSame('Resource Metadata', $crawler->filter('.tab:nth-of-type(1) .tab-title')->html());
        $tabContent = $crawler->filter('.tab:nth-of-type(1) .tab-content');
        $this->assertStringEndsWith('"Dummy"', $tabContent->filter('h3')->html(), 'the resource shortname should be displayed.');

        $this->assertCount(4, $tabContent->filter('table'));
        $this->assertSame('Item operations', $tabContent->filter('table:first-of-type thead th:first-of-type')->html());
        $this->assertSame('Collection operations', $tabContent->filter('table:nth-of-type(2) thead th:first-of-type')->html());
        $this->assertSame('Filters', $tabContent->filter('table:nth-of-type(3) thead th:first-of-type')->html());
        $this->assertSame('Attributes', $tabContent->filter('table:last-of-type thead th:first-of-type')->html());

        // Data providers tab
        $this->assertSame('Data Providers', $crawler->filter('.tab:nth-of-type(2) .tab-title')->html());
        $this->assertNotEmpty($crawler->filter('.tab:nth-of-type(2) .tab-content'));

        // Data persisters tab
        $this->assertSame('Data Persisters', $crawler->filter('.tab:last-child .tab-title')->html());
        $this->assertNotEmpty($crawler->filter('.tab:nth-of-type(3) .tab-content'));
    }

    public function testGetCollectionProfiler()
    {
        $client = static::createClient();
        $client->enableProfiler();
        $client->request('GET', '/dummies', [], [], ['HTTP_ACCEPT' => 'application/ld+json']);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $crawler = $client->request('GET', '/_profiler/latest?panel=api_platform.data_collector.request');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Metadata tab
        $tabContent = $crawler->filter('.tab:nth-of-type(1) .tab-content');
        $this->assertSame('get', $tabContent->filter('table:nth-of-type(2) th.status-success')->html(), 'The actual operation should be highlighted.');
        $this->assertEmpty($tabContent->filter('table:not(:nth-of-type(2)) .status-success'), 'Only the actual operation should be highlighted.');

        // Data provider tab
        $tabContent = $crawler->filter('.tab:nth-of-type(2) .tab-content');
        $this->assertSame('TRUE', $tabContent->filter('table tbody .status-success')->html());
        $this->assertContains('mongodb' === $this->env ? OdmCollectionDataProvider::class : CollectionDataProvider::class, $tabContent->filter('table tbody')->html());

        $this->assertContains('No calls to item data provider have been recorded.', $tabContent->html());
        $this->assertContains('No calls to subresource data provider have been recorded.', $tabContent->html());

        // Data persiters tab
        $this->assertContains('No calls to data persister have been recorded.', $crawler->filter('.tab:nth-of-type(3) .tab-content .empty')->html());
    }

    public function testPostCollectionProfiler()
    {
        $client = static::createClient();
        $client->enableProfiler();
        $client->request('POST', '/dummies', [], [], ['HTTP_ACCEPT' => 'application/ld+json', 'CONTENT_TYPE' => 'application/ld+json'], '{"name": "foo"}');
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $crawler = $client->request('get', '/_profiler/latest?panel=api_platform.data_collector.request');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Metadata tab
        $tabContent = $crawler->filter('.tab:nth-of-type(1) .tab-content');
        $this->assertSame('post', $tabContent->filter('table:nth-of-type(2) th.status-success')->html(), 'The actual operation should be highlighted.');
        $this->assertEmpty($tabContent->filter('table:not(:nth-of-type(2)) .status-success'), 'Only the actual operation should be highlighted.');

        // Data provider tab
        $tabContent = $crawler->filter('.tab:nth-of-type(2) .tab-content');
        $this->assertContains('No calls to collection data provider have been recorded.', $tabContent->html());
        $this->assertContains('No calls to item data provider have been recorded.', $tabContent->html());
        $this->assertContains('No calls to subresource data provider have been recorded.', $tabContent->html());

        // Data persiters tab
        $tabContent = $crawler->filter('.tab:nth-of-type(3) .tab-content');
        $this->assertSame('TRUE', $tabContent->filter('table tbody .status-success')->html());
        $this->assertContains(DataPersister::class, $tabContent->filter('table tbody')->html());
    }

    /**
     * @group legacy
     * Group legacy is due ApiPlatform\Core\Exception\ResourceClassNotSupportedException, the annotation could be removed in 3.0 but the test should stay
     */
    public function testGetItemProfiler()
    {
        $dummy = new Dummy();
        $dummy->setName('bar');
        $this->manager->persist($dummy);
        $this->manager->flush();

        $client = static::createClient();
        $client->enableProfiler();
        $client->request('GET', '/dummies/1', [], [], ['HTTP_ACCEPT' => 'application/ld+json', 'CONTENT_TYPE' => 'application/ld+json'], '{"name": "foo"}');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $crawler = $client->request('get', '/_profiler/latest?panel=api_platform.data_collector.request');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Metadata tab
        $tabContent = $crawler->filter('.tab:nth-of-type(1) .tab-content');
        $this->assertSame('get', $tabContent->filter('table:nth-of-type(1) th.status-success')->html(), 'The actual operation should be highlighted.');
        $this->assertEmpty($tabContent->filter('table:not(:nth-of-type(1)) .status-success'), 'Only the actual operation should be highlighted.');

        // Data provider tab
        $tabContent = $crawler->filter('.tab:nth-of-type(2) .tab-content');
        $this->assertSame('FALSE', $tabContent->filter('table tbody tr:first-of-type .status-error')->html());
        $this->assertSame(ContainNonResourceItemDataProvider::class, $tabContent->filter('table tbody tr:first-of-type td:nth-of-type(3)')->html());

        $this->assertSame('TRUE', $tabContent->filter('table tbody .status-success')->html());
        $this->assertContains('mongodb' === $this->env ? OdmItemDataProvider::class : ItemDataProvider::class, $tabContent->filter('table tbody')->html());

        $this->assertContains('No calls to collection data provider have been recorded.', $tabContent->html());
        $this->assertContains('No calls to subresource data provider have been recorded.', $tabContent->html());

        // Data persiters tab
        $this->assertContains('No calls to data persister have been recorded.', $crawler->filter('.tab:nth-of-type(3) .tab-content .empty')->html());
    }
}
