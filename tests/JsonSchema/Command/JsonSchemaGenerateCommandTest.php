<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Tests\JsonSchema\Command;

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @author Jacques Lefebvre <jacques@les-tilleuls.coop>
 */
class JsonSchemaGenerateCommandTest extends KernelTestCase
{
    /**
     * @var ApplicationTester
     */
    private $tester;

    protected function setUp(): void
    {
        self::bootKernel();

        $application = new Application(static::$kernel);
        $application->setCatchExceptions(true);
        $application->setAutoExit(false);

        $this->tester = new ApplicationTester($application);
    }

    public function testExecuteWithoutOption()
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => Dummy::class]);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithItemOperationGet()
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => Dummy::class, '--itemOperation' => 'get']);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithCollectionOperationGet()
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => Dummy::class, '--collectionOperation' => 'get']);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithTooManyOptions()
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => Dummy::class, '--collectionOperation' => 'get', '--itemOperation' => 'get']);

        $this->assertStringContainsString('You can only use one of "--itemOperation" and "--collectionOperation" options at the same time.', $this->tester->getDisplay());
    }

    public function testExecuteWithJsonldFormatOption()
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => Dummy::class, '--format' => 'jsonld']);
        $result = $this->tester->getDisplay();

        $this->assertStringContainsString('@id', $result);
        $this->assertStringContainsString('@context', $result);
        $this->assertStringContainsString('@type', $result);
    }
}
