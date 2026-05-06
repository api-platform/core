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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\InterfaceTaxon;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\InterfaceTaxonProduct;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class InterfaceAsResourceTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [InterfaceTaxon::class, InterfaceTaxonProduct::class];
    }

    public function testRetrieveTaxonViaInterface(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_interface_taxa/WONDERFUL_TAXON', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSame([
            '@context' => '/contexts/JsonLdInterfaceTaxon',
            '@id' => '/jsonld_interface_taxa/WONDERFUL_TAXON',
            '@type' => 'JsonLdInterfaceTaxon',
            'code' => 'WONDERFUL_TAXON',
        ], $response->toArray());
    }

    public function testRetrieveProductWithMainTaxonReferencesInterfaceResource(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_interface_taxon_products/GREAT_PRODUCT', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('JsonLdInterfaceTaxonProduct', $body['@type']);
        $this->assertSame('GREAT_PRODUCT', $body['code']);
        $this->assertIsArray($body['mainTaxon']);
        $this->assertSame('/jsonld_interface_taxa/WONDERFUL_TAXON', $body['mainTaxon']['@id']);
        $this->assertSame('JsonLdInterfaceTaxon', $body['mainTaxon']['@type']);
        $this->assertSame('WONDERFUL_TAXON', $body['mainTaxon']['code']);
    }
}
