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

namespace ApiPlatform\Tests\Functional\Filter;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PropertyFilter\SparseFieldsetChild;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PropertyFilter\SparseFieldsetParent;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PropertyFilter\SparseFieldsetParentWithQueryParameter;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Covers PropertyFilter sparse fieldset selection on resource relations.
 * Non-resource selection is covered by {@see \ApiPlatform\Tests\Functional\JsonLd\NonResourceTest::testSparseFieldsetOnNonResourceObject}.
 */
final class PropertyFilterTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [
            SparseFieldsetParent::class,
            SparseFieldsetParentWithQueryParameter::class,
            SparseFieldsetChild::class,
        ];
    }

    public function testApiFilterSelectsScalarProperties(): void
    {
        $response = self::createClient()->request(
            'GET',
            '/sparse_fieldset_parents/1?properties[]=name&properties[]=alias&properties[]=nameConverted',
            ['headers' => ['Accept' => 'application/ld+json']],
        );

        $body = $response->toArray();
        $this->assertSame('Parent #1', $body['name']);
        $this->assertSame('Alias #1', $body['alias']);
        // The name converter snake_cases this property at serialization time.
        $this->assertSame('Converted 1', $body['name_converted']);
        $this->assertArrayNotHasKey('child', $body);
    }

    public function testApiFilterSelectsNestedRelationProperty(): void
    {
        $response = self::createClient()->request(
            'GET',
            '/sparse_fieldset_parents/1?properties[]=name&properties[child][]=name',
            ['headers' => ['Accept' => 'application/ld+json']],
        );

        $body = $response->toArray();
        $this->assertSame('Parent #1', $body['name']);
        $this->assertSame('Child #1', $body['child']['name']);
        $this->assertArrayNotHasKey('description', $body['child']);
        $this->assertArrayNotHasKey('alias', $body);
    }

    public function testQueryParameterSelectsScalarProperties(): void
    {
        $response = self::createClient()->request(
            'GET',
            '/sparse_fieldset_parents_qp/1?properties[]=name&properties[]=alias',
            ['headers' => ['Accept' => 'application/ld+json']],
        );

        $body = $response->toArray();
        $this->assertSame('Parent #1', $body['name']);
        $this->assertSame('Alias #1', $body['alias']);
        $this->assertArrayNotHasKey('child', $body);
        $this->assertArrayNotHasKey('nameConverted', $body);
    }

    public function testQueryParameterSelectsNestedRelationProperty(): void
    {
        $response = self::createClient()->request(
            'GET',
            '/sparse_fieldset_parents_qp/1?properties[]=name&properties[child][]=name',
            ['headers' => ['Accept' => 'application/ld+json']],
        );

        $body = $response->toArray();
        $this->assertSame('Parent #1', $body['name']);
        $this->assertSame('Child #1', $body['child']['name']);
        $this->assertArrayNotHasKey('description', $body['child']);
    }
}
