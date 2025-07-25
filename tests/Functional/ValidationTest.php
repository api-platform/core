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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7228\ValidationGroupSequence;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class ValidationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ValidationGroupSequence::class];
    }

    public function testValidationGroupSequence(): void
    {
        $this->createClient()->request('POST', 'issue7228', ['headers' => ['content-type' => 'application/ld+json'], 'json' => ['id' => '1']]);
        $this->assertResponseIsSuccessful();
    }
}
