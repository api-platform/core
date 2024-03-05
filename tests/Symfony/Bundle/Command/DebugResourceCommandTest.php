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

use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use ApiPlatform\Symfony\Bundle\Command\DebugResourceCommand;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AlternateResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

class DebugResourceCommandTest extends TestCase
{
    use ProphecyTrait;

    private function getCommandTester(?DataDumperInterface $dumper = null): CommandTester
    {
        $application = new Application();
        $application->setCatchExceptions(false);
        $application->setAutoExit(false);

        $application->add(new DebugResourceCommand(new AttributesResourceMetadataCollectionFactory(), new VarCloner(), $dumper ?? new CliDumper()));

        $command = $application->find('debug:api-resource');

        return new CommandTester($command);
    }

    public function testDebugResource(): void
    {
        $varDumper = $this->prophesize(DataDumperInterface::class);
        $commandTester = $this->getCommandTester($varDumper->reveal());
        $varDumper->dump(Argument::any())->shouldBeCalledTimes(1);
        $commandTester->setInputs(['0', '0']);
        $commandTester->execute([
            'class' => AttributeResource::class,
        ]);

        $this->assertStringContainsString('Successfully dumped the selected resource', $commandTester->getDisplay());
    }

    public function testDebugOperation(): void
    {
        $varDumper = $this->prophesize(DataDumperInterface::class);
        $commandTester = $this->getCommandTester($varDumper->reveal());
        $varDumper->dump(Argument::any())->shouldBeCalledTimes(1);
        $commandTester->setInputs(['0', '1']);

        $commandTester->execute([
            'class' => AttributeResource::class,
        ]);

        $this->assertStringContainsString('Successfully dumped the selected operation', $commandTester->getDisplay());
    }

    public function testWithOnlyOneResource(): void
    {
        $varDumper = $this->prophesize(DataDumperInterface::class);
        $commandTester = $this->getCommandTester($varDumper->reveal());
        $varDumper->dump(Argument::any())->shouldBeCalledTimes(1);
        $commandTester->setInputs(['1']);

        $commandTester->execute([
            'class' => AlternateResource::class,
        ]);

        $this->assertStringContainsString('declares 1 resource', $commandTester->getDisplay());
        $this->assertStringContainsString('Successfully dumped the selected operation', $commandTester->getDisplay());
    }

    public function testExecuteWithNotExistingClass(): void
    {
        $this->expectException(ResourceClassNotFoundException::class);
        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            'class' => 'ApiPlatform\Tests\Fixtures\TestBundle\Entity\NotExisting',
        ]);
    }
}
