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

namespace ApiPlatform\Core\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Context for Symfony commands.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class CommandContext implements Context
{
    private $kernel;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var CommandTester
     */
    private $commandTester;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @When I run the command :command
     */
    public function iRunTheCommand(string $command): void
    {
        $command = $this->getApplication()->find($command);

        $this->getCommandTester($command)->execute([]);
    }

    /**
     * @When I run the command :command with options:
     */
    public function iRunTheCommandWithOptions(string $command, TableNode $options): void
    {
        $command = $this->getApplication()->find($command);

        $this->getCommandTester($command)->execute($options->getRowsHash());
    }

    /**
     * @Then the command output should be:
     */
    public function theCommandOutputShouldBe(PyStringNode $expectedOutput): void
    {
        Assert::assertEquals($expectedOutput->getRaw(), $this->commandTester->getDisplay());
    }

    /**
     * @Then the command output should contain:
     */
    public function theCommandOutputShouldContain(PyStringNode $expectedOutput): void
    {
        $expectedOutput = str_replace('###', '"""', $expectedOutput->getRaw());

        Assert::assertStringContainsString($expectedOutput, $this->commandTester->getDisplay());
    }

    public function setKernel(KernelInterface $kernel): void
    {
        $this->kernel = $kernel;
    }

    public function getApplication(): Application
    {
        if (null !== $this->application) {
            return $this->application;
        }

        $this->application = new Application($this->kernel);

        return $this->application;
    }

    private function getCommandTester(Command $command): CommandTester
    {
        $this->commandTester = new CommandTester($command);

        return $this->commandTester;
    }
}
