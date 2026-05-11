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

namespace ApiPlatform\Tests\Functional\Hal;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal\UriTemplateCar;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ItemUriTemplateTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [UriTemplateCar::class];
    }

    public function testCollectionWithoutItemUriTemplateUsesFirstGetOperation(): void
    {
        $response = self::createClient()->request('GET', '/hal_uri_template_cars', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();

        $this->assertSame('/hal_uri_template_cars', $body['_links']['self']['href']);
        $this->assertCount(2, $body['_links']['item']);
        foreach ($body['_links']['item'] as $link) {
            $this->assertMatchesRegularExpression('#^/hal_uri_template_cars/.+$#', $link['href']);
        }
        $this->assertCount(2, $body['_embedded']['item']);
        foreach ($body['_embedded']['item'] as $item) {
            $this->assertMatchesRegularExpression('#^/hal_uri_template_cars/.+$#', $item['_links']['self']['href']);
            $this->assertSame('Vincent', $item['owner']);
        }
    }

    public function testCollectionWithItemUriTemplateGeneratesIriFromTargetOperation(): void
    {
        $response = self::createClient()->request('GET', '/hal_uri_template_brands/renault/cars', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();

        $this->assertSame('/hal_uri_template_brands/renault/cars', $body['_links']['self']['href']);
        $this->assertCount(2, $body['_links']['item']);
        foreach ($body['_links']['item'] as $link) {
            $this->assertMatchesRegularExpression('#^/hal_uri_template_brands/renault/cars/.+$#', $link['href']);
        }
        $this->assertCount(2, $body['_embedded']['item']);
        foreach ($body['_embedded']['item'] as $item) {
            $this->assertMatchesRegularExpression('#^/hal_uri_template_brands/renault/cars/.+$#', $item['_links']['self']['href']);
        }
    }
}
