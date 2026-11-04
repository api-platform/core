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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\HydraOperationsCompany;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class HydraOperationsTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [HydraOperationsCompany::class];
    }

    public function testReferencedOperationsAreFilteredBySecurity(): void
    {
        $response = self::createClient()->request('GET', '/hydra_operations_companies/1', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertArrayNotHasKey('hydra:operation', $response->toArray());

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));

        $response = $client->request('GET', '/hydra_operations_companies/1', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertSame(['PATCH'], array_column($response->toArray()['hydra:operation'], 'hydra:method'));

        // The archive operation is only granted to the owner of the company
        $response = $client->request('GET', '/hydra_operations_companies/2', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertArrayNotHasKey('hydra:operation', $response->toArray());
    }

    public function testOperationHiddenFromTheDocumentationIsExposedToAuthorizedUsers(): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));

        $hydraOperations = $client->request('GET', '/hydra_operations_companies/2', ['headers' => ['Accept' => 'application/ld+json']])->toArray()['hydra:operation'];
        $this->assertSame(['DELETE', 'PATCH'], array_column($hydraOperations, 'hydra:method'));
        $this->assertSame([
            '@type' => ['hydra:Operation', 'schema:DeleteAction'],
            'hydra:description' => 'Deletes the HydraOperationsCompany resource.',
            'hydra:method' => 'DELETE',
            'hydra:title' => 'deleteHydraOperationsCompany',
            'returns' => 'owl:Nothing',
        ], $hydraOperations[0]);

        $supportedOperations = [];
        foreach ($client->request('GET', '/docs.jsonld')->toArray()['hydra:supportedClass'] as $supportedClass) {
            if ('HydraOperationsCompany' === $supportedClass['hydra:title']) {
                $supportedOperations = $supportedClass['hydra:supportedOperation'];
            }
        }

        // hideHydraOperation only removes the DELETE operation from the documentation, the response reuses its JSON-LD
        $this->assertSame(['GET', 'PATCH'], array_column($supportedOperations, 'hydra:method'));
        $this->assertSame($supportedOperations[1], $hydraOperations[1]);
    }

    public function testCollectionExposesTheOperationsSharingItsIri(): void
    {
        $response = self::createClient()->request('GET', '/hydra_operations_companies', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertSame(['GET'], array_column($response->toArray()['hydra:operation'], 'hydra:method'));

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));

        $data = $client->request('GET', '/hydra_operations_companies', ['headers' => ['Accept' => 'application/ld+json']])->toArray();
        $this->assertSame(['GET', 'POST'], array_column($data['hydra:operation'], 'hydra:method'));
        // Members expose the operations referenced by the operation identifying them
        $this->assertSame(['DELETE'], array_column($data['hydra:member'][0]['hydra:operation'], 'hydra:method'));
        $this->assertSame(['DELETE', 'PATCH'], array_column($data['hydra:member'][1]['hydra:operation'], 'hydra:method'));
    }
}
