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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\UrlEncodedId;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class UrlEncodedIdTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [UrlEncodedId::class];
    }

    public static function urlVariants(): iterable
    {
        yield 'raw colon and percent' => ['/url_encoded_ids/%encode:id'];
        yield 'fully encoded' => ['/url_encoded_ids/%25encode%3Aid'];
        yield 'encoded percent only' => ['/url_encoded_ids/%25encode:id'];
        yield 'encoded colon only' => ['/url_encoded_ids/%encode%3Aid'];
    }

    #[DataProvider('urlVariants')]
    public function testGetEncodedIdWhetherOrNotEncoded(string $url): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('UrlEncodedId fixture is ORM-only.');
        }

        $this->recreateSchema([UrlEncodedId::class]);

        $client = self::createClient();
        $manager = $this->getManager();
        $entity = new UrlEncodedId();
        $manager->persist($entity);
        $manager->flush();
        $manager->clear();

        $client->request('GET', $url, [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/UrlEncodedId',
            '@id' => '/url_encoded_ids/%25encode:id',
            '@type' => 'UrlEncodedId',
            'id' => '%encode:id',
        ]);
    }
}
