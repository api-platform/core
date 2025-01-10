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

class MakeStateProcessorCommandTest extends TestCase
{
    use WithWorkbench;

    /** @var string */
    private const STATE_PROCESSOR_COMMAND = 'make:state-processor';
    /** @var string */
    private const CHOSEN_CLASS_NAME = 'Choose a class name for your state processor (e.g. <fg=yellow>AwesomeStateProcessor</>)';

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
    public function testMakeStateProviderCommand(): void
    {
        $processorName = 'MyStateProcessor';
        $filePath = $this->pathResolver->generateStateFilename($processorName);
        $appServiceProviderPath = $this->pathResolver->getServiceProviderFilePath();

        $this->artisan(self::STATE_PROCESSOR_COMMAND)
            ->expectsQuestion(self::CHOSEN_CLASS_NAME, $processorName)
            ->expectsOutputToContain('Success!')
            ->expectsOutputToContain("created: $filePath")
            ->expectsOutputToContain('Next: Open your new state processor class and start customizing it.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertFileExists($filePath);

        $appServiceProviderContent = $this->filesystem->get($appServiceProviderPath);
        $this->assertStringContainsString('use ApiPlatform\State\ProcessorInterface;', $appServiceProviderContent);
        $this->assertStringContainsString("use App\State\\$processorName;", $appServiceProviderContent);
        $this->assertStringContainsString('$this->app->tag(MyStateProcessor::class, ProcessorInterface::class);', $appServiceProviderContent);

        $this->filesystem->delete($filePath);
    }

    public function testWhenStateProviderClassAlreadyExists(): void
    {
        $processorName = 'ExistingProcessor';
        $existingFile = $this->pathResolver->generateStateFilename($processorName);
        $this->filesystem->put($existingFile, '<?php // Existing processor');

        $expectedError = \sprintf('[ERROR] The file "%s" can\'t be generated because it already exists.', $existingFile);

        $this->artisan(self::STATE_PROCESSOR_COMMAND)
            ->expectsQuestion(self::CHOSEN_CLASS_NAME, $processorName)
            ->expectsOutput($expectedError)
            ->assertExitCode(Command::FAILURE);

        $this->filesystem->delete($existingFile);
    }

    public function testMakeStateProviderCommandWithoutGivenClassName(): void
    {
        $processorName = 'NoEmptyClassName';
        $filePath = $this->pathResolver->generateStateFilename($processorName);

        $this->artisan(self::STATE_PROCESSOR_COMMAND)
            ->expectsQuestion(self::CHOSEN_CLASS_NAME, '')
            ->expectsOutput('[ERROR] This value cannot be blank.')
            ->expectsQuestion(self::CHOSEN_CLASS_NAME, $processorName)
            ->assertExitCode(Command::SUCCESS);

        $this->assertFileExists($filePath);

        $this->filesystem->delete($filePath);
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
