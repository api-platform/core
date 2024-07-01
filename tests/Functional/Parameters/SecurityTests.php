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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\InMemoryUser;

class SecurityTests extends ApiTestCase
{
    public function dataUserAuthorization(): iterable
    {
        yield [['ROLE_ADMIN'], Response::HTTP_OK];
        yield [['ROLE_USER'], Response::HTTP_FORBIDDEN];
    }

    /** @dataProvider dataUserAuthorization */
    public function testUserAuthorization(array $roles, int $expectedStatusCode): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('emmanuel', 'password', $roles));

        $client->request('GET', 'with_security_parameters_collection?name=foo');
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    public function testNoValueParameter(): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('emmanuel', 'password', ['ROLE_ADMIN']));

        $client->request('GET', 'with_security_parameters_collection?name');
        $this->assertResponseIsSuccessful();
    }

    public function dataSecurityValues(): iterable
    {
        yield ['secured', Response::HTTP_OK];
        yield ['not_the_expected_parameter_value', Response::HTTP_UNAUTHORIZED];
    }

    /** @dataProvider dataSecurityValues */
    public function testSecurityHeaderValues(string $parameterValue, int $expectedStatusCode): void
    {
        self::createClient()->request('GET', 'with_security_parameters_collection', [
            'headers' => [
                'auth' => $parameterValue,
            ],
        ]);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    /** @dataProvider dataSecurityValues */
    public function testSecurityQueryValues(string $parameterValue, int $expectedStatusCode): void
    {
        self::createClient()->request('GET', sprintf('with_security_parameters_collection?secret=%s', $parameterValue));
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }
}
