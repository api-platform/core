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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\GraphQlCircularReference\CircularReferenceAddress;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\GraphQlCircularReference\CircularReferenceCustomer;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Two ApiResource entities with mutual ManyToOne/OneToMany references must not crash
 * the GraphQL ItemNormalizer when the traversal hits a circular reference. The default
 * circular_reference_handler returns an IRI string; the GraphQL normalizer used to
 * assert the result was an array and threw "Expected data to be an array.".
 *
 * @see https://github.com/api-platform/core/issues/8080
 */
final class CircularReferenceNormalizationTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CircularReferenceCustomer::class, CircularReferenceAddress::class];
    }

    public function testCircularReferenceTraversalDoesNotCrash(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('CircularReference entities are ORM-only.');
        }

        $this->recreateSchema([CircularReferenceCustomer::class, CircularReferenceAddress::class]);

        $manager = $this->getManager();
        $customer = new CircularReferenceCustomer();
        $customer->name = 'Acme';

        $address = new CircularReferenceAddress();
        $address->street = '1 Test Street';
        $address->owner = $customer;
        $customer->addresses->add($address);
        $customer->invoiceAddress = $address;

        $manager->persist($customer);
        $manager->persist($address);
        $manager->flush();

        $iri = '/circular_reference_addresses/'.$address->id;

        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => <<<GRAPHQL
            {
              circularReferenceAddress(id: "{$iri}") {
                id
                street
                owner {
                  id
                  name
                }
              }
            }
            GRAPHQL,
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        // Pre-fix the inner normalization triggered "Expected data to be an array."
        // because the circular_reference_handler returned the IRI string.
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));
        $this->assertSame('1 Test Street', $json['data']['circularReferenceAddress']['street']);
        $this->assertSame('Acme', $json['data']['circularReferenceAddress']['owner']['name']);
    }
}
