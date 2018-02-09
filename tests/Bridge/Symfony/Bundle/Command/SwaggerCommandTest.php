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

        $this->assertYaml($this->tester->getDisplay());
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
