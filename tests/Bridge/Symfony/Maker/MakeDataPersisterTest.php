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

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Question;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class MakeDataPersisterTest extends KernelTestCase
{
    protected function tearDown(): void
    {
        (new Filesystem())->remove(self::tempDir());
    }

    /** @dataProvider dataPersisterProvider */
    public function testMakeDataPersister(array $commandInputs, array $userInputs, string $expected)
    {
        $this->assertFileDoesNotExist(self::tempFile('src/DataPersister/CustomDataPersister.php'));

        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:data-persister'));
        $tester->setInputs($userInputs);
        $tester->execute($commandInputs);

        $this->assertFileExists(self::tempFile('src/DataPersister/CustomDataPersister.php'));

        // Unify line endings
        $expected = preg_replace('~\R~u', "\r\n", $expected);
        $result = preg_replace('~\R~u', "\r\n", file_get_contents(self::tempFile('src/DataPersister/CustomDataPersister.php')));
        $this->assertSame($expected, $result);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('Success!', $display);

        if (!isset($commandInputs['name'])) {
            $this->assertStringContainsString('Choose a class name for your data persister (e.g. AwesomeDataPersister):', $display);
        } else {
            $this->assertStringNotContainsString('Choose a class name for your data persister (e.g. AwesomeDataPersister):', $display);
        }
        if (!isset($commandInputs['resource-class'])) {
            $this->assertStringContainsString('Choose a Resource class:', $display);
        } else {
            $this->assertStringNotContainsString('Choose a Resource class:', $display);
        }
        $this->assertStringContainsString(<<<EOF
 Next: Open your new data persister class and start customizing it.
 Find the documentation at https://api-platform.com/docs/core/data-persisters/
EOF
            , $display);
    }

    public function dataPersisterProvider(): Generator
    {
        $expected = <<<'EOF'
<?php

namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\DataPersister\ResumableDataPersisterInterface;

final class CustomDataPersister implements ContextAwareDataPersisterInterface, ResumableDataPersisterInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports($data, array $context = []): bool
    {
        return false; // Add your custom conditions here
    }

    /**
     * {@inheritdoc}
     */
    public function resumable(array $context = []): bool
    {
        return false; // Set it to true if you want to call the other data persisters
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data, array $context = []): object
    {
        // Call your persistence layer to save $data

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data, array $context = []): void
    {
        // Call your persistence layer to delete $data
    }
}

EOF;
        yield 'Generate data persister without resource class' => [
            [],
            ['CustomDataPersister', ''],
            \PHP_VERSION_ID >= 70200 ? $expected : str_replace(': object', '', $expected),
        ];

        $expected = <<<'EOF'
<?php

namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\DataPersister\ResumableDataPersisterInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Question;

final class CustomDataPersister implements ContextAwareDataPersisterInterface, ResumableDataPersisterInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports($data, array $context = []): bool
    {
        return $data instanceof Question::class; // Add your custom conditions here
    }

    /**
     * {@inheritdoc}
     */
    public function resumable(array $context = []): bool
    {
        return false; // Set it to true if you want to call the other data persisters
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data, array $context = []): Question
    {
        // Call your persistence layer to save $data

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data, array $context = []): void
    {
        // Call your persistence layer to delete $data
    }
}

EOF;
        yield 'Generate data persister with resource class' => [
            [],
            ['CustomDataPersister', Question::class],
            $expected,
        ];

        yield 'Generate data persister with resource class not interactively' => [
            ['name' => 'CustomDataPersister', 'resource-class' => Question::class],
            [],
            $expected,
        ];
    }

    private static function tempDir(): string
    {
        return __DIR__.'/../../../Fixtures/app/var/tmp';
    }

    private static function tempFile(string $path): string
    {
        return sprintf('%s/%s', self::tempDir(), $path);
    }
}
