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

class MakeApiResourceTest extends KernelTestCase
{
    protected function setup(): void
    {
        (new Filesystem())->remove(self::tempDir());
    }

    public function testMakeMinimalResource(): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:api-resource'));
        $tester->setInputs([
            '',     // no fields
            '',     // no operations
            'no',   // provider
            'no',   // processor
        ]);
        $tester->execute(['name' => 'Minimal']);

        $resourceFile = self::tempFile('src/ApiResource/Minimal.php');
        $this->assertFileExists($resourceFile);

        $expected = preg_replace('~\R~u', "\n", file_get_contents(__DIR__.'/../../Fixtures/Symfony/Maker/MinimalApiResource.fixture'));
        $result = preg_replace('~\R~u', "\n", file_get_contents($resourceFile));
        $this->assertStringContainsString($expected, $result);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('Success!', $display);
    }

    public function testMakeResourceWithValidation(): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:api-resource'));
        $tester->setInputs([
            'name',                 // field name
            'string',               // type: string
            'no',                   // nullable
            'yes',                  // validated
            '',                     // stop fields
            'Post',                 // Post
            '',                     // no more operations
            'no',                   // provider
            'yes',                  // processor
        ]);
        $tester->execute(['name' => 'Book']);

        $resourceFile = self::tempFile('src/ApiResource/Book.php');
        $processorFile = self::tempFile('src/State/BookProcessor.php');
        $this->assertFileExists($resourceFile);
        $this->assertFileExists($processorFile);

        $expectedResource = preg_replace('~\R~u', "\n", file_get_contents(__DIR__.'/../../Fixtures/Symfony/Maker/EntityApiResource.fixture'));
        $resultResource = preg_replace('~\R~u', "\n", file_get_contents($resourceFile));
        $this->assertStringContainsString($expectedResource, $resultResource);

        $expectedProcessor = preg_replace('~\R~u', "\n", file_get_contents(__DIR__.'/../../Fixtures/Symfony/Maker/EntityApiResourceStateProcessor.fixture'));
        $resultProcessor = preg_replace('~\R~u', "\n", file_get_contents($processorFile));
        $this->assertStringContainsString($expectedProcessor, $resultProcessor);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('Success!', $display);
    }

    public function testMakeResourceWithCustomNamespace(): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:api-resource'));
        $tester->setInputs([
            '',     // no fields
            '',     // no operations
            'no',   // provider
            'no',   // processor
        ]);
        $tester->execute(['name' => 'Minimal', '--namespace-prefix' => 'Api\\Resource\\']);

        $resourceFile = self::tempFile('src/Api/Resource/Minimal.php');
        $this->assertFileExists($resourceFile);

        $expected = preg_replace('~\R~u', "\n", file_get_contents(__DIR__.'/../../Fixtures/Symfony/Maker/NamespacedMinimalApiResource.fixture'));
        $result = preg_replace('~\R~u', "\n", file_get_contents($resourceFile));
        $this->assertStringContainsString($expected, $result);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('Success!', $display);
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
