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

namespace ApiPlatform\Tests\Symfony\Bundle\Command;

use ApiPlatform\OpenApi\OpenApi;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 *
 * TODO Remove group legacy in 4.0
 *
 * @group legacy
 */
class OpenApiCommandTest extends KernelTestCase
{
    use ExpectDeprecationTrait;

    private ApplicationTester $tester;

    protected function setUp(): void
    {
        self::bootKernel();

        $application = new Application(static::$kernel);
        $application->setCatchExceptions(false);
        $application->setAutoExit(false);
        $this->tester = new ApplicationTester($application);

        $this->handleDeprecations();
    }

    public function testExecute(): void
    {
        $this->tester->run(['command' => 'api:openapi:export']);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithYaml(): void
    {
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
        $assertExample($json['components']['schemas']['Issue6317.jsonld.output']['properties'], 'id');
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

    /**
     * TODO Remove in 4.0.
     */
    private function handleDeprecations(): void
    {
        $this->expectDeprecation('Since api-platform/core 3.1: The "%s" option is deprecated, use "openapi" instead.');
    }
}
