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

namespace ApiPlatform\Tests\OpenApi\Command;

use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\DummyCar;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Crud;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6317\Issue6317;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5625\Currency;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class OpenApiCommandTest extends KernelTestCase
{
    use SetupClassResourcesTrait;

    private ApplicationTester $tester;

    protected function setUp(): void
    {
        self::bootKernel();

        $application = new Application(static::$kernel);
        $application->setCatchExceptions(false);
        $application->setAutoExit(false);
        $this->tester = new ApplicationTester($application);
    }

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            DummyCar::class,
            Issue6317::class,
            Currency::class,
            Crud::class,
        ];
    }

    public function testExecute(): void
    {
        $this->tester->run(['command' => 'api:openapi:export']);

        $this->assertJson($this->tester->getDisplay());
    }

    #[\PHPUnit\Framework\Attributes\Group('orm')]
    public function testExecuteWithYaml(): void
    {
        // $this->setMetadataClasses([DummyCar::class, Currency::class]);
        $this->tester->run(['command' => 'api:openapi:export', '--yaml' => true]);

        $result = $this->tester->getDisplay();

        $this->assertYaml($result);
        $operationId = 'api_dummy_cars_get_collection';

        $expected = <<<YAML
  /dummy_cars:
    get:
      operationId: $operationId
      tags:
        - DummyCar
YAML;

        $this->assertStringContainsString(str_replace(\PHP_EOL, "\n", $expected), $result, 'nested object should be present.');

        $operationId = 'api_dummy_cars_id_get';
        $expected = <<<YAML
  '/dummy_cars/{id}':
    get:
      operationId: $operationId
      tags: []
YAML;

        $this->assertStringContainsString(str_replace(\PHP_EOL, "\n", $expected), $result, 'arrays should be correctly formatted.');
        $this->assertStringContainsString('openapi: '.OpenApi::VERSION, $result);

        $expected = <<<YAML
info:
  title: 'My Dummy API'
YAML;
        $this->assertStringContainsString(str_replace(\PHP_EOL, "\n", $expected), $result, 'multiline formatting must be preserved (using literal style).');

        $expected = <<<YAML
    This is a test API.
    Made with love
  version: 0.0.0
YAML;

        $this->assertStringContainsString(str_replace(\PHP_EOL, "\n", $expected), $result);

        $expected = <<<YAML
      security:
        -
          JWT:
            - CURRENCY_READ
YAML;
        $this->assertStringContainsString(str_replace(\PHP_EOL, "\n", $expected), $result);
    }

    public function testWriteToFile(): void
    {
        // $this->setMetadataClasses([DummyCar::class]);
        /** @var string $tmpFile */
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_write_to_file');

        $this->tester->run(['command' => 'api:openapi:export', '--output' => $tmpFile]);

        $this->assertJson((string) @file_get_contents($tmpFile));
        @unlink($tmpFile);
    }

    /**
     * Test issue #6317.
     */
    public function testBackedEnumExamplesAreNotLost(): void
    {
        // $this->setMetadataClasses([Issue6317::class]);
        $this->tester->run(['command' => 'api:openapi:export']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, true, 512, \JSON_THROW_ON_ERROR);

        $assertExample = function (array $properties, string $id): void {
            $this->assertArrayHasKey('example', $properties[$id]);          // default
            $this->assertArrayHasKey('example', $properties['cardinal']);   // openapiContext
            $this->assertArrayNotHasKey('example', $properties['name']);    // jsonSchemaContext
            $this->assertArrayNotHasKey('example', $properties['ordinal']); // jsonldContext
        };

        $assertExample($json['components']['schemas']['Issue6317']['properties'], 'id');
        $assertExample($json['components']['schemas']['Issue6317.jsonld']['properties'], 'id');
        $assertExample($json['components']['schemas']['Issue6317.jsonapi']['properties']['data']['properties']['attributes']['properties'], '_id');
        $assertExample($json['components']['schemas']['Issue6317.jsonhal']['properties'], 'id');
    }

    private function assertYaml(string $data): void
    {
        try {
            Yaml::parse($data);
        } catch (ParseException $exception) {
            $this->fail('Is not valid YAML: '.$exception->getMessage());
        }
        $this->addToAssertionCount(1);
    }

    public function testFilterXApiPlatformTag(): void
    {
        $this->tester->run(['command' => 'api:openapi:export', '--filter-tags' => 'anotherone']);
        $result = $this->tester->getDisplay();
        $res = json_decode($result, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('Crud', $res['components']['schemas']);
        $this->assertArrayNotHasKey('/cruds/{id}', $res['paths']);
        $this->assertArrayHasKey('/cruds', $res['paths']);
        $this->assertArrayNotHasKey('post', $res['paths']['/cruds']);
        $this->assertArrayHasKey('get', $res['paths']['/cruds']);
        $this->assertEquals([['name' => 'Crud']], $res['tags']);
    }
}
