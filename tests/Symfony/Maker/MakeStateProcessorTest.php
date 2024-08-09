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

namespace ApiPlatform\Tests\Symfony\Maker;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class MakeStateProcessorTest extends KernelTestCase
{
    protected function setup(): void
    {
        (new Filesystem())->remove(self::tempDir());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('stateProcessorProvider')]
    public function testMakeStateProcessor(bool $isInteractive): void
    {
        $inputs = ['name' => 'CustomStateProcessor'];
        $newProcessorFile = self::tempFile('src/State/CustomStateProcessor.php');

        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:state-processor'));
        $tester->setInputs($isInteractive ? $inputs : []);
        $tester->execute($isInteractive ? [] : $inputs);

        $this->assertFileExists($newProcessorFile);

        // Unify line endings
        $expected = preg_replace('~\R~u', "\r\n", file_get_contents(__DIR__.'/../../Fixtures/Symfony/Maker/CustomStateProcessor.fixture'));
        $result = preg_replace('~\R~u', "\r\n", file_get_contents($newProcessorFile));
        $this->assertStringContainsString($expected, $result);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('Success!', $display);

        $notInteractiveOutput = 'Choose a class name for your state processor (e.g. AwesomeStateProcessor):';

        if ($isInteractive) {
            $this->assertStringContainsString($notInteractiveOutput, $display);
        } else {
            $this->assertStringNotContainsString($notInteractiveOutput, $display);
        }

        $this->assertStringContainsString('Next: Open your new state processor class and start customizing it.', $display);
    }

    public static function stateProcessorProvider(): \Generator
    {
        yield 'Generate state processor' => [
            'isInteractive' => true,
        ];

        yield 'Generate state processor not interactively' => [
            'isInteractive' => false,
        ];
    }

    private static function tempDir(): string
    {
        return __DIR__.'/../../Fixtures/app/var/tmp';
    }

    private static function tempFile(string $path): string
    {
        return \sprintf('%s/%s', self::tempDir(), $path);
    }
}
