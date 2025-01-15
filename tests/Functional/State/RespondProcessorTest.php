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

namespace ApiPlatform\Tests\Functional\State;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyGetPostDeleteOperation;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

class RespondProcessorTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyGetPostDeleteOperation::class];
    }

    public function testAllowHeadersForSingleResourceWithGetDelete(): void
    {
        $client = static::createClient();
        $client->request('GET', '/dummy_get_post_delete_operations/1', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseHeaderSame('Allow', 'OPTIONS, HEAD, GET, DELETE');
    }

    public function testAllowHeadersForResourceCollectionReflectsAllowedMethods(): void
    {
        $client = static::createClient();
        $client->request('GET', '/dummy_get_post_delete_operations', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseHeaderSame('allow', 'OPTIONS, HEAD, GET, POST');
    }

    public function testAcceptPostHeaderForResourceWithPostReflectsAllowedTypes(): void
    {
        $client = static::createClient();
        $client->request('GET', '/dummy_get_post_delete_operations', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseHeaderSame('accept-post', 'application/ld+json, application/hal+json, application/vnd.api+json, application/xml, text/xml, application/json, text/html, application/graphql, multipart/form-data');
    }

    public function testAcceptPostHeaderDoesNotExistResourceWithoutPost(): void
    {
        $client = static::createClient();
        $client->request('OPTIONS', '/dummy_get_post_delete_operations/1', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseNotHasHeader('accept-post');
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();
        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        $classes = [$manager->getClassMetadata(DummyGetPostDeleteOperation::class)];

        try {
            $schemaTool = new SchemaTool($manager);
            @$schemaTool->createSchema($classes);
        } catch (\Exception $e) {
            return;
        }

        $dummy = new DummyGetPostDeleteOperation();
        $dummy->setName('Dummy');
        $manager->persist($dummy);
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

        $classes = [$manager->getClassMetadata(DummyGetPostDeleteOperation::class)];

        $schemaTool = new SchemaTool($manager);
        @$schemaTool->dropSchema($classes);
        parent::tearDown();
    }
}
