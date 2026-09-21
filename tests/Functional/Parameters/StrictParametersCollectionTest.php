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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\StrictParametersCollection;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * @see https://github.com/api-platform/core/issues/8543
 */
final class StrictParametersCollectionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [StrictParametersCollection::class];
    }

    public function testPaginationParametersAreAllowed(): void
    {
        self::createClient()->request('GET', 'strict_query_parameters_collection?page=1&itemsPerPage=5&pagination=false');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testUnknownParameterIsStillRejected(): void
    {
        self::createClient()->request('GET', 'strict_query_parameters_collection?unknown=1');
        $this->assertJsonContains(['detail' => 'Parameter "unknown" not supported']);
        $this->assertResponseStatusCodeSame(400);
    }
}
