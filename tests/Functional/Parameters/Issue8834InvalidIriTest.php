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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8834Resource;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class Issue8834InvalidIriTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [Issue8834Resource::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema(self::getResources());
    }

    public function testInvalidScalarIriReturnsBadRequest(): void
    {
        self::createClient()->request('GET', '/issue8834_resources?related=/invalid/iri');
        self::assertResponseStatusCodeSame(400);
    }

    public function testInvalidArrayIriReturnsBadRequest(): void
    {
        self::createClient()->request('GET', '/issue8834_resources?related[]=/invalid/iri');
        self::assertResponseStatusCodeSame(400);
    }
}
