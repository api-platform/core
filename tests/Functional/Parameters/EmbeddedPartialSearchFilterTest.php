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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\PostCard as DocumentPostCard;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\PostCardAddress as DocumentPostCardAddress;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PostCard;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PostCardAddress;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Tests that PartialSearchFilter and FreeTextQueryFilter work correctly
 * with Doctrine embedded objects (ORM\Embedded / ORM\Embeddable).
 *
 * @see https://github.com/api-platform/core/issues/7862
 */
final class EmbeddedPartialSearchFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [PostCard::class];
    }

    protected function setUp(): void
    {
        $entities = $this->isMongoDB()
            ? [DocumentPostCard::class]
            : [PostCard::class];

        $this->recreateSchema($entities);
        $this->loadFixtures();
    }

    public function testPartialSearchOnEmbeddedProperty(): void
    {
        $response = self::createClient()->request('GET', '/post_cards_embedded?citySearch=Paris');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
        $this->assertSame('Greetings from Paris', $data['member'][0]['title']);
    }

    public function testPartialSearchOnEmbeddedPropertyPartialMatch(): void
    {
        $response = self::createClient()->request('GET', '/post_cards_embedded?citySearch=ar');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
    }

    public function testFreeTextSearchOnEmbeddedProperties(): void
    {
        $response = self::createClient()->request('GET', '/post_cards_embedded?freeSearch=Paris');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
        $this->assertSame('Greetings from Paris', $data['member'][0]['title']);
    }

    public function testFreeTextSearchOnEmbeddedPropertiesMatchesStreet(): void
    {
        $response = self::createClient()->request('GET', '/post_cards_embedded?freeSearch=Broadway');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
        $this->assertSame('Hello from New York', $data['member'][0]['title']);
    }

    public function testPartialSearchOnEmbeddedPropertyNoMatch(): void
    {
        $response = self::createClient()->request('GET', '/post_cards_embedded?citySearch=Tokyo');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(0, $data['member']);
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();
        $isMongoDB = $this->isMongoDB();

        $addressClass = $isMongoDB ? DocumentPostCardAddress::class : PostCardAddress::class;
        $postCardClass = $isMongoDB ? DocumentPostCard::class : PostCard::class;

        $address1 = new $addressClass();
        $address1->setCity('Paris');
        $address1->setStreet('Champs-Élysées');
        $address1->setZipCode('75008');

        $postCard1 = new $postCardClass();
        $postCard1->setTitle('Greetings from Paris');
        $postCard1->setAddress($address1);

        $address2 = new $addressClass();
        $address2->setCity('New York');
        $address2->setStreet('Broadway');
        $address2->setZipCode('10001');

        $postCard2 = new $postCardClass();
        $postCard2->setTitle('Hello from New York');
        $postCard2->setAddress($address2);

        $manager->persist($postCard1);
        $manager->persist($postCard2);
        $manager->flush();
    }
}
