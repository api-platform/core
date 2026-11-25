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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\CachedMetadataLinkResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CachedMetadataArticle;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CachedMetadataComment;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class RequestValuesInCachedMetadataTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CachedMetadataLinkResource::class, CachedMetadataArticle::class, CachedMetadataComment::class];
    }

    public function testLinkSecurityUsesCurrentRequestValue(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $client->request('GET', '/cached_metadata_secured_links/1');
        $this->assertResponseStatusCodeSame(200);

        $client->request('GET', '/cached_metadata_secured_links/2');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testLinkConstraintsUseCurrentRequestValue(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $client->request('GET', '/cached_metadata_constrained_links/-5');
        $this->assertResponseStatusCodeSame(422);

        $client->request('GET', '/cached_metadata_constrained_links/1');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIriResolutionDoesNotUsePreviousQueryParameterValue(): void
    {
        if ('mongodb' === static::getContainer()->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $client = static::createClient();
        $client->disableReboot();
        $this->recreateSchema([CachedMetadataArticle::class, CachedMetadataComment::class]);

        $manager = $this->getManager();
        $published = new CachedMetadataArticle();
        $published->published = true;
        $draft = new CachedMetadataArticle();
        $manager->persist($published);
        $manager->persist($draft);
        $manager->flush();

        $client->request('GET', '/cached_metadata_articles/'.$published->id.'?published=false');

        $client->request('POST', '/cached_metadata_comments', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['article' => '/cached_metadata_articles/'.$draft->id],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }
}
