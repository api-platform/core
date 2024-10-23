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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FilteredBooleanParameter as FilteredBooleanParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilteredBooleanParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class BooleanFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    /** @var string */
    private const ROUTE = '/filtered_boolean_parameters';

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [FilteredBooleanParameter::class];
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testBooleanFilterWithValidValue(): void
    {
        $resource = $this->isMongoDB() ? FilteredBooleanParameterDocument::class : FilteredBooleanParameter::class;
        $this->recreateSchema([$resource]);
        $this->loadFixtures($resource);

        $this->assertBooleanFilterResponse('true', 2);
        $this->assertBooleanFilterResponse('false', 1);
        $this->assertBooleanFilterResponse('1', 2);
        $this->assertBooleanFilterResponse('0', 1);
    }

    public function testBooleanFilterWithInvalidValue(): void
    {
        $this->assertBooleanFilterResponse('null', 3); // <=> ignored
        $this->assertValidationErrorResponse('');
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testBooleanFilterAliasWithValidValue(): void
    {
        $resource = $this->isMongoDB() ? FilteredBooleanParameterDocument::class : FilteredBooleanParameter::class;
        $this->recreateSchema([$resource]);
        $this->loadFixtures($resource);

        $this->assertBooleanFilterResponse('true', 2, 'enabled');
        $this->assertBooleanFilterResponse('false', 1, 'enabled');
        $this->assertBooleanFilterResponse('1', 2, 'enabled');
        $this->assertBooleanFilterResponse('0', 1, 'enabled');
    }

    public function testBooleanFilterAliasWithInvalidValue(): void
    {
        $this->assertBooleanFilterResponse('null', 3, 'enabled'); // <=> ignored
        $this->assertValidationErrorResponse('', 'enabled');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function assertBooleanFilterResponse(?string $activeValue, int $expectedCount, string $param = 'active'): void
    {
        $route = $this->getRoute($activeValue, $param);
        $response = self::createClient()->request('GET', $route);
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $entities = $data['hydra:member'];

        $this->assertCount($expectedCount, $entities, \sprintf('The number of items with %s=%s should be %d', $param, $activeValue, $expectedCount));

        if ('null' !== $activeValue) {
            foreach ($entities as $entity) {
                $isActiveExpected = \in_array($activeValue, ['true', '1'], true);
                $expectedValue = $isActiveExpected ? 'true' : 'false';
                $message = \sprintf("Expected 'active' to be %s", $expectedValue);

                $this->assertSame($isActiveExpected, $entity['active'], $message);
            }
        }
    }

    private function assertValidationErrorResponse(string $activeValue, string $param = 'active'): void
    {
        $route = $this->getRoute($activeValue, $param);
        $response = self::createClient()->request('GET', $route);

        $this->assertEquals(422, $response->getStatusCode(), 'Expected status code 422 for validation error.');
    }

    private function loadFixtures(string $resource): void
    {
        $manager = $this->getManager();

        $entitiesData = [true, true, false, null];
        foreach ($entitiesData as $activeValue) {
            $entity = new $resource(active: $activeValue);
            $manager->persist($entity);
        }

        $manager->flush();
    }

    private function getRoute(?string $value, string $param = 'active'): string
    {
        return \sprintf('%s?%s=%s', self::ROUTE, $param, $value);
    }
}
