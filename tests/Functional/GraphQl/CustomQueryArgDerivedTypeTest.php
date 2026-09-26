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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8473\Widget;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8473\WidgetStatusOptions;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CustomQueryArgDerivedTypeTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [WidgetStatusOptions::class, Widget::class];
    }

    public function testSchemaBuildsAndResolvesCustomArgType(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => <<<'GRAPHQL'
{
  __type(name: "Query") {
    fields {
      name
      args {
        name
        type {
          kind
          ofType {
            kind
            name
          }
        }
      }
    }
  }
}
GRAPHQL,
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json);

        $statusArg = null;
        foreach ($json['data']['__type']['fields'] as $candidate) {
            foreach ($candidate['args'] as $arg) {
                if ('status' === $arg['name']) {
                    $statusArg = $arg;
                    break 2;
                }
            }
        }

        $this->assertNotNull($statusArg, 'No query field with a "status" argument was found.');

        $this->assertSame('NON_NULL', $statusArg['type']['kind']);
        $this->assertSame('ENUM', $statusArg['type']['ofType']['kind']);
        $this->assertSame('WidgetStatus', $statusArg['type']['ofType']['name']);
    }

    public function testMaterializedTypesRemainQueryableAfterSchemaBuild(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => <<<'GRAPHQL'
{
  widget: __type(name: "Widget") {
    fields {
      name
    }
  }
  options: __type(name: "WidgetStatusOptions") {
    fields {
      name
    }
  }
}
GRAPHQL,
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json);

        $widgetFieldNames = array_column($json['data']['widget']['fields'], 'name');
        $this->assertContains('status', $widgetFieldNames);

        $optionsFieldNames = array_column($json['data']['options']['fields'], 'name');
        $this->assertContains('options', $optionsFieldNames);
    }
}
