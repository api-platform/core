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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\WithParameter;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class HydraTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [WithParameter::class];
    }

    public function testHydraTemplate(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters_collection?hydra=1');
        $this->assertArraySubset(['hydra:search' => [
            'hydra:template' => '/with_parameters_collection{?hydra}',
            'hydra:mapping' => [
                ['@type' => 'IriTemplateMapping', 'variable' => 'hydra', 'property' => 'a', 'required' => true],
            ],
        ]], $response->toArray());
    }
}
