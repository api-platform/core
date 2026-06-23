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

namespace ApiPlatform\Tests\Functional\GraphQl;

use ApiPlatform\GraphQl\Test\GraphQlTestTrait;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\GraphQlFilteredResource as GraphQlFilteredResourceDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\GraphQlFilteredResourceColor as GraphQlFilteredResourceColorDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\GraphQlFilteredResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\GraphQlFilteredResourceColor;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Parity safety-net: the GraphQL filter *arguments* generated from the canonical
 * Operation::getParameters() (#[QueryParameter]) path must match what the legacy
 * Operation::getFilters() (#[ApiFilter]) path produces on DummyCar/DummyCarColor
 * (see FilterTest::testNestedCollectionFilter and the ComparisonFilter operator forms).
 *
 * Covers the three parity gaps the unified FieldsBuilder arg-tree pipeline closes:
 * the nested `colors(prop:)` argument from a dotted parameter key, the
 * ComparisonFilter gt/gte/lt/lte/ne operator form, and the `order: [..]` list shape.
 */
final class ParameterFilterParityTest extends ApiTestCase
{
    use GraphQlTestTrait;
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            GraphQlFilteredResource::class,
            GraphQlFilteredResourceColor::class,
        ];
    }

    private function recreate(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? GraphQlFilteredResourceDocument::class : GraphQlFilteredResource::class,
            $this->isMongoDB() ? GraphQlFilteredResourceColorDocument::class : GraphQlFilteredResourceColor::class,
        ]);
    }

    public function testNestedCollectionSearchArgumentFromQueryParameter(): void
    {
        $this->recreate();
        $this->seedResourceWithColors();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              graphQlFilteredResource(id: "/graph_ql_filtered_resources/1") {
                id
                colors(prop: "blue") {
                  edges { node { id prop } }
                }
              }
            }
            QUERY);

        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));

        $edges = $json['data']['graphQlFilteredResource']['colors']['edges'];
        $this->assertCount(1, $edges);
        $this->assertSame('blue', $edges[0]['node']['prop']);
    }

    public function testComparisonOperatorArgumentFromQueryParameter(): void
    {
        $this->recreate();
        $this->seedResourceWithColors();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              graphQlFilteredResource(id: "/graph_ql_filtered_resources/1") {
                id
                colors(price: {gt: 10}) {
                  edges { node { id prop price } }
                }
              }
            }
            QUERY);

        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));

        $edges = $json['data']['graphQlFilteredResource']['colors']['edges'];
        $this->assertCount(1, $edges);
        $this->assertSame('blue', $edges[0]['node']['prop']);
    }

    public function testRootExactSearchArgumentFromQueryParameter(): void
    {
        $this->recreate();
        $this->seedResourceWithColors();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              graphQlFilteredResources(name: "mustli") {
                edges { node { id name } }
              }
            }
            QUERY);

        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));

        $edges = $json['data']['graphQlFilteredResources']['edges'];
        $this->assertCount(1, $edges);
        $this->assertSame('mustli', $edges[0]['node']['name']);
    }

    public function testOrderArgumentFromQueryParameter(): void
    {
        $this->recreate();
        $this->seedResourceWithColors();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              graphQlFilteredResources(order: [{name: "DESC"}]) {
                edges { node { id name } }
              }
            }
            QUERY);

        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));
        $this->assertResponseIsSuccessful();
    }

    private function seedResourceWithColors(): void
    {
        $manager = $this->getManager();
        $resourceClass = $this->isMongoDB() ? GraphQlFilteredResourceDocument::class : GraphQlFilteredResource::class;
        $colorClass = $this->isMongoDB() ? GraphQlFilteredResourceColorDocument::class : GraphQlFilteredResourceColor::class;

        $resource = new $resourceClass();
        $resource->setName('mustli');
        $manager->persist($resource);
        $manager->flush();

        $red = new $colorClass();
        $red->setProp('red');
        $red->setPrice(5);
        $red->setResource($resource);
        $manager->persist($red);

        $blue = new $colorClass();
        $blue->setProp('blue');
        $blue->setPrice(20);
        $blue->setResource($resource);
        $manager->persist($blue);
        $manager->flush();

        $resource->setColors(new ArrayCollection([$red, $blue]));
        $manager->persist($resource);
        $manager->flush();
    }
}
