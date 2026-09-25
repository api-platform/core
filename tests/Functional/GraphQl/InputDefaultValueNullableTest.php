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

namespace ApiPlatform\Tests\Functional\GraphQl;

use ApiPlatform\GraphQl\Test\GraphQlTestTrait;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GraphQlInputDefaultTags;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class InputDefaultValueNullableTest extends ApiTestCase
{
    use GraphQlTestTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [GraphQlInputDefaultTags::class];
    }

    public function testPropertyWithDefaultValueIsNullableOnCreateInput(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              __type(name: "createGraphQlInputDefaultTagsInput") {
                inputFields { name type { kind } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $fields = $response->toArray()['data']['__type']['inputFields'];
        $byName = [];
        foreach ($fields as $field) {
            $byName[$field['name']] = $field['type']['kind'];
        }

        $this->assertSame('NON_NULL', $byName['title']);
        $this->assertNotSame('NON_NULL', $byName['tags']);
    }
}
