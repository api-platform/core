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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\CompanyWithChoiceValidation;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * @see https://github.com/api-platform/core/issues/1522
 */
final class ChoiceValidationOpenApiTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CompanyWithChoiceValidation::class];
    }

    public function testChoiceConstraintIsDocumentedInOpenApi(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();

        $json = $response->toArray();
        $this->assertArrayHasKey('CompanyWithChoiceValidation', $json['components']['schemas']);

        $properties = $json['components']['schemas']['CompanyWithChoiceValidation']['properties'];

        $this->assertSame(['SARL', 'SAS', 'SA'], $properties['companyType']['enum']);
        $this->assertSame(['SARL', 'SAS', 'SA', 'EURL'], $properties['companyTypeFromCallback']['enum']);

        $this->assertSame('array', $properties['allowedCompanyTypes']['type']);
        $this->assertSame(['SARL', 'SAS', 'SA'], $properties['allowedCompanyTypes']['items']['enum']);
        $this->assertSame('string', $properties['allowedCompanyTypes']['items']['type']);
        $this->assertSame(1, $properties['allowedCompanyTypes']['minItems']);
        $this->assertSame(3, $properties['allowedCompanyTypes']['maxItems']);
    }
}
