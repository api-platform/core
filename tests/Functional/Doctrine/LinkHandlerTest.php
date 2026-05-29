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

namespace ApiPlatform\Tests\Functional\Doctrine;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\LinkHandledDummy as LinkHandledDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\LinkHandledDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class LinkHandlerTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [LinkHandledDummy::class];
    }

    public function testGetCollectionFiltersBySlugViaLinksHandler(): void
    {
        $resource = $this->isMongoDB() ? LinkHandledDummyDocument::class : LinkHandledDummy::class;
        $this->recreateSchema([$resource]);

        $manager = $this->getManager();
        foreach (['foo', 'bar', 'baz', 'foz'] as $slug) {
            $manager->persist(new $resource($slug));
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/link_handled_dummies', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $response->toArray()['hydra:totalItems']);
    }

    public function testGetItemReturnsSlug(): void
    {
        $resource = $this->isMongoDB() ? LinkHandledDummyDocument::class : LinkHandledDummy::class;
        $this->recreateSchema([$resource]);

        $manager = $this->getManager();
        foreach (['foo', 'bar', 'baz', 'foz'] as $slug) {
            $manager->persist(new $resource($slug));
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/link_handled_dummies/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('foo', $response->toArray()['slug']);
    }
}
