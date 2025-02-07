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

namespace ApiPlatform\Tests\Functional\Issues;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6926\Error;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6926\ThrowsAnExceptionWithGroup;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class Issue6926Test extends ApiTestCase
{
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ThrowsAnExceptionWithGroup::class, Error::class];
    }

    public function testWithGroupFilter(): void
    {
        $response = self::createClient()->request('POST', '/issue6926', ['json' => []]);
        $this->assertEquals('This should be returned in the response.', $response->toArray(false)['detail'] ?? false);
    }
}
