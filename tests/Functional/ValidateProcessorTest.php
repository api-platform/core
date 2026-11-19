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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ValidateOnce;
use ApiPlatform\Tests\Fixtures\TestBundle\Validator\CountableConstraintValidator;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Tests that validation is not run twice for resources without ObjectMapper.
 *
 * ValidateProvider (provider chain) and ValidateProcessor (processor chain) should
 * not both validate the same data. ValidateProcessor should only run when
 * ObjectMapper is used (canMap() is true).
 */
class ValidateProcessorTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            ValidateOnce::class,
        ];
    }

    public function testValidationRunsOnlyOnceWithoutObjectMapper(): void
    {
        CountableConstraintValidator::$count = 0;

        self::createClient()->request('POST', '/validate_once', [
            'json' => ['name' => 'test'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, CountableConstraintValidator::$count, 'Validation should run exactly once for resources without ObjectMapper.');
    }
}
