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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MathNumber;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Symfony\Component\Serializer\Normalizer\NumberNormalizer;

#[RequiresPhp('^8.4')]
#[RequiresPhpExtension('bcmath')]
final class MathNumberTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [MathNumber::class];
    }

    protected function setUp(): void
    {
        if (!class_exists(NumberNormalizer::class)) {
            $this->markTestSkipped('Requires BcMath/Number and symfony/serialiser >=7.3');
        }

        parent::setUp();
    }

    public function testGetMathNumber(): void
    {
        self::createClient()->request('GET', '/math_numbers/1', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '@context' => '/contexts/MathNumber',
            '@id' => '/math_numbers/1',
            '@type' => 'MathNumber',
            'id' => 1,
            'value' => '300.55',
        ]);
    }

    public function testPostMathNumber(): void
    {
        self::createClient()->request('POST', '/math_numbers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'id' => 2,
                'value' => '120.23',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '@context' => '/contexts/MathNumber',
            '@id' => '/math_numbers/2',
            '@type' => 'MathNumber',
            'id' => 2,
            'value' => '120.23',
        ]);
    }
}
