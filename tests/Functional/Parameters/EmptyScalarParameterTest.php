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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ChoiceConstrainedScalarParameter\ChoiceConstrainedScalarParameter;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class EmptyScalarParameterTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ChoiceConstrainedScalarParameter::class];
    }

    #[DataProvider('provideScalarParameters')]
    public function testEmptyValueIsRejectedByConstraints(string $parameter): void
    {
        $response = self::createClient()->request('GET', '/choice_constrained_scalar_parameters?'.$parameter.'=');

        $this->assertResponseStatusCodeSame(422);
        $this->assertArraySubset([
            'violations' => [
                ['propertyPath' => $parameter, 'message' => 'The value you selected is not a valid choice.'],
            ],
        ], $response->toArray(false));
    }

    public static function provideScalarParameters(): \Generator
    {
        yield 'integer' => ['days'];
        yield 'float' => ['ratio'];
        yield 'boolean' => ['active'];
    }

    public function testUncastableValueIsRejectedByConstraints(): void
    {
        self::createClient()->request('GET', '/choice_constrained_scalar_parameters?days=foo');

        $this->assertResponseStatusCodeSame(422);
    }
}
