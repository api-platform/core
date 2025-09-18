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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class MakeFilterTest extends KernelTestCase
{
    protected function setup(): void
    {
        (new Filesystem())->remove(self::tempDir());
    }

    #[DataProvider('filterProvider')]
    public function testMakeFilter(string $type, string $name, bool $isInteractive): void
    {
        $inputs = ['type' => $type, 'name' => $name];
        $newFilterFile = self::tempFile("src/Filter/{$name}.php");

        $command = (new Application(self::bootKernel()))->find('make:filter');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs($isInteractive ? $inputs : []);
        $commandTester->execute($isInteractive ? [] : $inputs);

        $this->assertFileExists($newFilterFile);

        $expected = preg_replace('~\R~u', "\r\n", file_get_contents(__DIR__."/../../Fixtures/Symfony/Maker/{$name}.fixture"));
        $result = preg_replace('~\R~u', "\r\n", file_get_contents($newFilterFile));
        $this->assertStringContainsString($expected, $result);

        $display = $commandTester->getDisplay();
        $commandTester->assertCommandIsSuccessful();
        $interactiveOutputType = 'Choose a type for your filter';
        $interactiveOutputName = 'Choose a class name for your filter';

        if ($isInteractive) {
            $this->assertStringContainsString($interactiveOutputType, $display);
            $this->assertStringContainsString($interactiveOutputName, $display);
        } else {
            $this->assertStringNotContainsString($interactiveOutputType, $display);
            $this->assertStringNotContainsString($interactiveOutputName, $display);
        }

        $this->assertStringContainsString(' Next: Open your filter class and start customizing it.', $display);
    }

    public static function filterProvider(): \Generator
    {
        yield 'Generate ORM filter' => ['orm', 'CustomOrmFilter', true];
        yield 'Generate ORM filter not interactively' => ['orm', 'CustomOrmFilter', false];
        yield 'Generate ODM filter' => ['odm', 'CustomOdmFilter', true];
        yield 'Generate ODM filter not interactively' => ['odm', 'CustomOdmFilter', false];
    }

    #[DataProvider('filterErrorProvider')]
    public function testCommandFailsWithInvalidInput(array $inputs, string $exceptionClass = InvalidArgumentException::class): void
    {
        $this->expectException($exceptionClass);
        $command = (new Application(self::bootKernel()))->find('make:filter');
        (new CommandTester($command))->execute($inputs);
    }

    public static function filterErrorProvider(): \Generator
    {
        yield 'Missing type and name arguments' => [
            [],
            MissingInputException::class,
        ];

        yield 'Invalid type argument' => [
            ['type' => 'john', 'name' => 'MyCustomFilter'],
        ];

        yield 'No valid type argument given' => [
            ['type' => 'John', 'name' => 'MyCustomFilter'],
        ];

        yield 'Missing name argument' => [
            ['type' => 'orm'],
            MissingInputException::class,
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
