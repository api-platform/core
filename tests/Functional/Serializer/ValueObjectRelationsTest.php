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

namespace ApiPlatform\Tests\Functional\Serializer;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\VoDummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\VoDummyDriver;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\VoDummyInspection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\VoDummyInsuranceCompany;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\VoDummyVehicle;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ValueObjectRelationsTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [VoDummyCar::class, VoDummyVehicle::class, VoDummyDriver::class, VoDummyInspection::class, VoDummyInsuranceCompany::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('ORM-only fixture; VoDummy hierarchy uses Doctrine ORM-specific cascading expectations.');
        }
        $this->recreateSchema(static::getResources());
    }

    public function testPostHydratesValueObjectViaConstructor(): void
    {
        self::createClient()->request(
            'POST',
            '/vo_dummy_cars',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode([
                    'mileage' => 1500,
                    'bodyType' => 'suv',
                    'make' => 'CustomCar',
                    'insuranceCompany' => ['name' => 'Safe Drive Company'],
                    'drivers' => [['firstName' => 'John', 'lastName' => 'Doe']],
                ]),
            ],
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals(<<<'JSON'
{
    "@context": "/contexts/VoDummyCar",
    "@id": "/vo_dummy_cars/1",
    "@type": "VoDummyCar",
    "mileage": 1500,
    "bodyType": "suv",
    "inspections": [],
    "make": "CustomCar",
    "insuranceCompany": {
        "@id": "/vo_dummy_insurance_companies/1",
        "@type": "VoDummyInsuranceCompany",
        "name": "Safe Drive Company"
    },
    "drivers": [{
        "@id": "/vo_dummy_drivers/1",
        "@type": "VoDummyDriver",
        "firstName": "John",
        "lastName": "Doe"
    }]
}
JSON);
    }

    public function testPostInspectionWithIriRelation(): void
    {
        $this->createCar();

        self::createClient()->request(
            'POST',
            '/vo_dummy_inspections',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode(['accepted' => true, 'car' => '/vo_dummy_cars/1']),
            ],
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesJsonSchema(<<<'JSON'
{
    "type": "object",
    "required": ["accepted", "performed", "car"],
    "properties": {
        "accepted": {"enum": [true]},
        "performed": {"format": "date-time"},
        "car": {"enum": ["/vo_dummy_cars/1"]}
    }
}
JSON);
    }

    public function testLegacyPutKeepsImmutableProperties(): void
    {
        $this->createCar();
        $this->createInspection();

        self::createClient()->request(
            'PUT',
            '/vo_dummy_inspections/1',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode(['performed' => '2018-08-24 00:00:00', 'accepted' => false]),
            ],
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals(<<<'JSON'
{
    "@context": "/contexts/VoDummyInspection",
    "@id": "/vo_dummy_inspections/1",
    "@type": "VoDummyInspection",
    "accepted": true,
    "car": "/vo_dummy_cars/1",
    "performed": "2018-08-24T00:00:00+00:00"
}
JSON);
    }

    public function testPatchKeepsImmutableProperties(): void
    {
        $this->createCar();
        $this->createInspection();

        self::createClient()->request(
            'PATCH',
            '/vo_dummy_inspections/1',
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode(['performed' => '2018-08-24 00:00:00', 'accepted' => false]),
            ],
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals(<<<'JSON'
{
    "@context": "/contexts/VoDummyInspection",
    "@id": "/vo_dummy_inspections/1",
    "@type": "VoDummyInspection",
    "accepted": true,
    "car": "/vo_dummy_cars/1",
    "performed": "2018-08-24T00:00:00+00:00"
}
JSON);
    }

    public function testMissingRequiredConstructorParameterReturnsError(): void
    {
        self::createClient()->request(
            'POST',
            '/vo_dummy_cars',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode([
                    'mileage' => 1500,
                    'make' => 'CustomCar',
                    'insuranceCompany' => ['name' => 'Safe Drive Company'],
                ]),
            ],
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertStringContainsString('<http://www.w3.org/ns/hydra/error>; rel="http://www.w3.org/ns/json-ld#error"', self::getClient()->getResponse()->headers->get('link') ?? '');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
    "type": "object",
    "required": ["@type", "detail"],
    "properties": {
        "@type": {"type": "string", "pattern": "^hydra:Error$"},
        "detail": {"pattern": "^Cannot create an instance of \"ApiPlatform\\\\Tests\\\\Fixtures\\\\TestBundle\\\\(Document|Entity)\\\\VoDummyCar\" from serialized data because its constructor requires the following parameters to be present : \"\\$drivers\".$"}
    }
}
JSON);
    }

    public function testDefaultConstructorParameterIsApplied(): void
    {
        self::createClient()->request(
            'POST',
            '/vo_dummy_cars',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode([
                    'mileage' => 1500,
                    'make' => 'CustomCar',
                    'insuranceCompany' => ['name' => 'Safe Drive Company'],
                    'drivers' => [['firstName' => 'John', 'lastName' => 'Doe']],
                ]),
            ],
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals(<<<'JSON'
{
    "@context": "/contexts/VoDummyCar",
    "@id": "/vo_dummy_cars/1",
    "@type": "VoDummyCar",
    "mileage": 1500,
    "bodyType": "coupe",
    "inspections": [],
    "make": "CustomCar",
    "insuranceCompany": {
        "@id": "/vo_dummy_insurance_companies/1",
        "@type": "VoDummyInsuranceCompany",
        "name": "Safe Drive Company"
    },
    "drivers": [{
        "@id": "/vo_dummy_drivers/1",
        "@type": "VoDummyDriver",
        "firstName": "John",
        "lastName": "Doe"
    }]
}
JSON);
    }

    private function createCar(): void
    {
        self::createClient()->request(
            'POST',
            '/vo_dummy_cars',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode([
                    'mileage' => 1500,
                    'bodyType' => 'suv',
                    'make' => 'CustomCar',
                    'insuranceCompany' => ['name' => 'Safe Drive Company'],
                    'drivers' => [['firstName' => 'John', 'lastName' => 'Doe']],
                ]),
            ],
        );
    }

    private function createInspection(): void
    {
        self::createClient()->request(
            'POST',
            '/vo_dummy_inspections',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode(['accepted' => true, 'car' => '/vo_dummy_cars/1']),
            ],
        );
    }
}
