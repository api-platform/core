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

namespace ApiPlatform\Tests\JsonSchema\Command;

use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DocumentDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @author Jacques Lefebvre <jacques@les-tilleuls.coop>
 */
class JsonSchemaGenerateCommandTest extends KernelTestCase
{
    private ApplicationTester $tester;

    private string $entityClass;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $application = new Application(static::$kernel);
        $application->setCatchExceptions(true);
        $application->setAutoExit(false);

        $this->entityClass = 'mongodb' === $kernel->getEnvironment() ? DocumentDummy::class : Dummy::class;
        $this->tester = new ApplicationTester($application);
    }

    public function testExecuteWithoutOption(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass]);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithItemOperationGet(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--operation' => '_api_/dummies/{id}{._format}_get', '--type' => 'output']);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithCollectionOperationGet(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--operation' => '_api_/dummies{._format}_get_collection', '--type' => 'output']);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithJsonldFormatOption(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--operation' => '_api_/dummies{._format}_post', '--format' => 'jsonld', '--type' => 'output']);
        $result = $this->tester->getDisplay();

        $this->assertStringContainsString('@id', $result);
        $this->assertStringContainsString('@context', $result);
        $this->assertStringContainsString('@type', $result);
    }

    public function testExecuteWithJsonldTypeInput(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--operation' => '_api_/dummies{._format}_post', '--format' => 'jsonld', '--type' => 'input']);
        $result = $this->tester->getDisplay();

        $this->assertStringNotContainsString('@id', $result);
        $this->assertStringNotContainsString('@context', $result);
        $this->assertStringNotContainsString('@type', $result);
    }
}
