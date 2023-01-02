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

namespace ApiPlatform\Tests\Metadata;

use ApiPlatform\Metadata\ApiResource;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class ApiResourceTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @group legacy
     */
    public function testIfElasticsearchParametersEqualsToTrueIsDeprecated(): void
    {
        self::expectDeprecation('Since api-platform/core 3.1: Setting "elasticsearch" is deprecated. Pass an instance of ApiPlatform\Elasticsearch\Metadata\ElasticsearchDocument to $persistenceMeans instead');
        $resource = new ApiResource(elasticsearch: true);
    }

    /**
     * @group legacy
     */
    public function testIfElasticsearchParametersEqualsToFalseIsDeprecated(): void
    {
        self::expectDeprecation('Since api-platform/core 3.1: Setting "elasticsearch" is deprecated. You will have to remove it when upgrading to v4');
        $resource = new ApiResource(elasticsearch: false);
    }
}
