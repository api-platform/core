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

namespace ApiPlatform\Tests\Functional\Parameters;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FreeTextArticle as DocumentFreeTextArticle;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FreeTextTag as DocumentFreeTextTag;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FreeTextArticle;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FreeTextTag;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class FreeTextQueryFilterNestedTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [FreeTextArticle::class, FreeTextTag::class];
    }

    /**
     * Tests that FreeTextQueryFilter with mixed nested and non-nested properties
     * generates correct SQL aliases (non-nested properties should not use the join alias).
     */
    public function testMixedNestedAndNonNestedProperties(): void
    {
        $client = $this->createClient();

        // Should match article1 by root content
        $response = $client->request('GET', '/free_text_articles?search=root-match')->toArray();
        $this->assertJsonContains(['totalItems' => 1]);

        // Should match article2 by tag.content
        $response = $client->request('GET', '/free_text_articles?search=tag-match')->toArray();
        $this->assertJsonContains(['totalItems' => 1]);

        // Should match both articles (article1 root content contains "shared", article3 tag content contains "shared")
        $response = $client->request('GET', '/free_text_articles?search=shared')->toArray();
        $this->assertJsonContains(['totalItems' => 2]);

        // Should match nothing
        $response = $client->request('GET', '/free_text_articles?search=nonexistent')->toArray();
        $this->assertJsonContains(['totalItems' => 0]);
    }

    protected function setUp(): void
    {
        $this->recreateSchema([FreeTextArticle::class, FreeTextTag::class]);
        $this->loadFixtures();
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();
        $isMongoDB = $this->isMongoDB();

        $tagClass = $isMongoDB ? DocumentFreeTextTag::class : FreeTextTag::class;
        $articleClass = $isMongoDB ? DocumentFreeTextArticle::class : FreeTextArticle::class;

        $tag1 = new $tagClass();
        $tag1->setContent('unrelated-tag');

        $tag2 = new $tagClass();
        $tag2->setContent('tag-match-value');

        $tag3 = new $tagClass();
        $tag3->setContent('shared-tag');

        $manager->persist($tag1);
        $manager->persist($tag2);
        $manager->persist($tag3);

        // article1: root content matches "root-match" and "shared", tag does not
        $article1 = new $articleClass();
        $article1->setContent('root-match-shared');
        $article1->setTag($tag1);

        // article2: root content does not match, but tag matches "tag-match"
        $article2 = new $articleClass();
        $article2->setContent('nothing-special');
        $article2->setTag($tag2);

        // article3: root content does not match "shared", but tag matches "shared"
        $article3 = new $articleClass();
        $article3->setContent('nothing-here');
        $article3->setTag($tag3);

        $manager->persist($article1);
        $manager->persist($article2);
        $manager->persist($article3);
        $manager->flush();
    }
}
