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
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class MakeDataProviderTest extends KernelTestCase
{
    protected function tearDown(): void
    {
        (new Filesystem())->remove(self::tempDir());
    }

    /** @dataProvider dataProviderProvider */
    public function testMakeDataProvider(array $commandInputs, array $userInputs, string $expected)
    {
        $this->assertFileDoesNotExist(self::tempFile('src/DataProvider/CustomDataProvider.php'));

        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:data-provider'));
        $tester->setInputs($userInputs);
        $tester->execute($commandInputs);

        $this->assertFileExists(self::tempFile('src/DataProvider/CustomDataProvider.php'));

        // Unify line endings
        $expected = preg_replace('~\R~u', "\r\n", $expected);
        $result = preg_replace('~\R~u', "\r\n", file_get_contents(self::tempFile('src/DataProvider/CustomDataProvider.php')));
        $this->assertSame($expected, $result);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('Success!', $display);

        if (!isset($commandInputs['name'])) {
            $this->assertStringContainsString('Choose a class name for your data provider (e.g. AwesomeDataProvider):', $display);
        } else {
            $this->assertStringNotContainsString('Choose a class name for your data provider (e.g. AwesomeDataProvider):', $display);
        }
        if (!isset($commandInputs['resource-class'])) {
            $this->assertStringContainsString(' Choose a Resource class:', $display);
        } else {
            $this->assertStringNotContainsString('Choose a Resource class:', $display);
        }

        $this->assertStringContainsString(<<<EOF
 Next: Open your new data provider class and start customizing it.
 Find the documentation at https://api-platform.com/docs/core/data-providers/
EOF
            , $display);
    }

    public function dataProviderProvider(): Generator
    {
        $expected = <<<'EOF'
<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;

final class CustomDataProvider implements ContextAwareCollectionDataProviderInterface, ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return false; // Add your custom conditions here
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        // Retrieve the collection from somewhere
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?object
    {
        // Retrieve the item from somewhere then return it or null if not found
    }
}

EOF;
        yield 'Generate all without resource class' => [
            [],
            ['CustomDataProvider', ''],
            \PHP_VERSION_ID >= 70200 ? $expected : str_replace(': ?object', '', $expected),
        ];

        $expected = <<<'EOF'
<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Question;

final class CustomDataProvider implements ContextAwareCollectionDataProviderInterface, ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Question::class === $resourceClass; // Add your custom conditions here
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        // Retrieve the collection from somewhere
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Question
    {
        // Retrieve the item from somewhere then return it or null if not found
    }
}

EOF;

        yield 'Generate all with resource class' => [
            [],
            ['CustomDataProvider', Question::class],
            $expected,
        ];

        yield 'Generate all with resource class not interactively' => [
            ['name' => 'CustomDataProvider', 'resource-class' => Question::class],
            [],
            $expected,
        ];

        $expected = <<<'EOF'
<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;

final class CustomDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return false; // Add your custom conditions here
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?object
    {
        // Retrieve the item from somewhere then return it or null if not found
    }
}

EOF;
        yield 'Generate an item data provider without resource class' => [
            ['--item-only' => true],
            ['CustomDataProvider', ''],
            \PHP_VERSION_ID >= 70200 ? $expected : str_replace(': ?object', '', $expected),
        ];

        $expected = <<<'EOF'
<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Question;

final class CustomDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Question::class === $resourceClass; // Add your custom conditions here
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Question
    {
        // Retrieve the item from somewhere then return it or null if not found
    }
}

EOF;
        yield 'Generate an item data provider with a resource class' => [
            ['--item-only' => true],
            ['CustomDataProvider', Question::class],
            $expected,
        ];

        yield 'Generate an item data provider with a resource class not interactively' => [
            ['name' => 'CustomDataProvider', 'resource-class' => Question::class, '--item-only' => true],
            [],
            $expected,
        ];

        $expected = <<<'EOF'
<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;

final class CustomDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return false; // Add your custom conditions here
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        // Retrieve the collection from somewhere
    }
}

EOF;
        yield 'Generate a collection data provider without a resource class' => [
            ['--collection-only' => true],
            ['CustomDataProvider', ''],
            $expected,
        ];

        $expected = <<<'EOF'
<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Question;

final class CustomDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Question::class === $resourceClass; // Add your custom conditions here
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        // Retrieve the collection from somewhere
    }
}

EOF;

        yield 'Generate a collection data provider with a resource class' => [
            ['--collection-only' => true],
            ['CustomDataProvider', Question::class],
            $expected,
        ];

        yield 'Generate a collection data provider with a resource class not interactively' => [
            ['name' => 'CustomDataProvider', 'resource-class' => Question::class, '--collection-only' => true],
            [],
            $expected,
        ];
    }

    public function testMakeDataProviderThrows()
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:data-provider'));
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('You should at least generate an item or a collection data provider');

        $tester->execute(['name' => 'CustomDataProvider', 'resource-class' => Question::class, '--collection-only' => true, '--item-only' => true]);
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
