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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class SwaggerCommandTest extends KernelTestCase
{
    /**
     * @var ApplicationTester
     */
    private $tester;

    protected function setUp()
    {
        self::bootKernel();

        $application = new Application(static::$kernel);
        $application->setCatchExceptions(false);
        $application->setAutoExit(false);

        $this->tester = new ApplicationTester($application);
    }

    public function testExecute()
    {
        $this->tester->run(['command' => 'api:swagger:export']);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithYaml()
    {
        $this->tester->run(['command' => 'api:swagger:export', '--yaml' => true]);

        $result = $this->tester->getDisplay();
        $this->assertYaml($result);

        $expected = <<<YAML
    /dummy_cars:
        get:
            tags:
                - DummyCar
            operationId: getDummyCarCollection
YAML;

        $this->assertContains($expected, $result, 'nested object should be present.');

        $expected = <<<YAML
    '/dummy_cars/{id}':
        get:
            tags: []
            operationId: getDummyCarItem
YAML;

        $this->assertContains($expected, $result, 'arrays should be correctly formatted.');
    }

    public function testWriteToFile()
    {
        /** @var string $tmpFile */
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_write_to_file');

        $this->tester->run(['command' => 'api:swagger:export', '--output' => $tmpFile]);

        $this->assertJson((string) @file_get_contents($tmpFile));
        @unlink($tmpFile);
    }

    /**
     * @param string $data
     */
    private function assertYaml($data)
    {
        try {
            Yaml::parse($data);
        } catch (ParseException $exception) {
            $this->fail('Is not valid YAML: '.$exception->getMessage());
        }
        $this->addToAssertionCount(1);
    }
}
