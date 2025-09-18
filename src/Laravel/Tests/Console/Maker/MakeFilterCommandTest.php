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

namespace ApiPlatform\Laravel\Tests\Console\Maker;

use ApiPlatform\Laravel\Tests\Console\Maker\Utils\AppServiceFileGenerator;
use ApiPlatform\Laravel\Tests\Console\Maker\Utils\PathResolver;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MakeFilterCommandTest extends TestCase
{
    use WithWorkbench;

    /** @var string */
    private const MAKE_FILTER_COMMAND = 'make:filter';
    /** @var string */
    private const FILTER_CLASS_NAME = 'Choose a class name for your filter (e.g. <fg=yellow>AwesomeFilter</>)';

    private Filesystem $filesystem;
    private PathResolver $pathResolver;
    private AppServiceFileGenerator $appServiceFileGenerator;

    /**
     * @throws FileNotFoundException
     */
    protected function setup(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->pathResolver = new PathResolver();
        $this->appServiceFileGenerator = new AppServiceFileGenerator($this->filesystem);

        $this->appServiceFileGenerator->regenerateProviderFile();
    }

    /**
     * @throws FileNotFoundException
     */
    public function testMakeStateFilterCommand(): void
    {
        $filterName = 'MyFilter';
        $filePath = $this->pathResolver->generateFilterFilename($filterName);
        $appServiceFilterPath = $this->pathResolver->getServiceProviderFilePath();

        $this->artisan(self::MAKE_FILTER_COMMAND)
            ->expectsQuestion(self::FILTER_CLASS_NAME, $filterName)
            ->expectsOutputToContain('Success!')
            ->expectsOutputToContain("created: $filePath")
            ->expectsOutputToContain('Next: Open your new Eloquent Filter class and start customizing it.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertFileExists($filePath);

        $appServiceFilterContent = $this->filesystem->get($appServiceFilterPath);
        $this->assertStringContainsString('use App\\Filter\\MyFilter;', $appServiceFilterContent);
        $this->assertStringContainsString('use ApiPlatform\Laravel\Eloquent\Filter\FilterInterface;', $appServiceFilterContent);
        $this->assertStringContainsString('$this->app->tag(MyFilter::class, FilterInterface::class);', $appServiceFilterContent);

        $this->filesystem->delete($filePath);
    }

    public function testWhenStateFilterClassAlreadyExists(): void
    {
        $filterName = 'ExistingFilter';
        $existingFile = $this->pathResolver->generateFilterFilename($filterName);
        $this->filesystem->put($existingFile, '<?php // Existing filter');

        $expectedError = \sprintf('[ERROR] The file "%s" can\'t be generated because it already exists.', $existingFile);

        $this->artisan(self::MAKE_FILTER_COMMAND)
            ->expectsQuestion(self::FILTER_CLASS_NAME, $filterName)
            ->expectsOutput($expectedError)
            ->assertExitCode(Command::FAILURE);

        $this->filesystem->delete($existingFile);
    }

    #[DataProvider('nullProvider')]
    public function testMakeStateFilterCommandWithoutGivenClassName(?string $value): void
    {
        $this->artisan(self::MAKE_FILTER_COMMAND)
            ->expectsQuestion(self::FILTER_CLASS_NAME, $value)
            ->assertExitCode(Command::FAILURE);
    }

    public static function nullProvider(): \Generator
    {
        yield 'null value used' => ['value' => null];
        yield 'empty string used' => ['value' => ''];
    }

    /**
     * @throws FileNotFoundException
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->appServiceFileGenerator->regenerateProviderFile();
    }
}
