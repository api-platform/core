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

class MakeStateProviderTest extends KernelTestCase
{
    protected function setup(): void
    {
        (new Filesystem())->remove(self::tempDir());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('stateProviderDataProvider')]
    public function testMakeStateProvider(bool $isInteractive): void
    {
        $inputs = ['name' => 'CustomStateProvider'];
        $newProviderFile = self::tempFile('src/State/CustomStateProvider.php');

        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:state-provider'));
        $tester->setInputs($isInteractive ? $inputs : []);
        $tester->execute($isInteractive ? [] : $inputs);

        $this->assertFileExists($newProviderFile);

        // Unify line endings
        $expected = preg_replace('~\R~u', "\r\n", file_get_contents(__DIR__.'/../../Fixtures/Symfony/Maker/CustomStateProvider.fixture'));
        $result = preg_replace('~\R~u', "\r\n", file_get_contents($newProviderFile));
        $this->assertStringContainsString($expected, $result);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('Success!', $display);

        $notInteractiveOutput = 'Choose a class name for your state provider (e.g. AwesomeStateProvider):';

        if ($isInteractive) {
            $this->assertStringContainsString($notInteractiveOutput, $display);
        } else {
            $this->assertStringNotContainsString($notInteractiveOutput, $display);
        }

        $this->assertStringContainsString('Next: Open your new state provider class and start customizing it.', $display);
    }

    public static function stateProviderDataProvider(): \Generator
    {
        yield 'Generate state provider' => [
            'isInteractive' => true,
        ];

        yield 'Generate state provider not interactively' => [
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
