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

class MakeStateProviderCommandTest extends TestCase
{
    use WithWorkbench;

    /** @var string */
    private const MAKE_STATE_PROVIDER_COMMAND = 'make:state-provider';
    /** @var string */
    private const STATE_PROVIDER_CLASS_NAME = 'Choose a class name for your state provider (e.g. <fg=yellow>AwesomeStateProvider</>)';

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
        $providerName = 'MyStateProvider';
        $filePath = $this->pathResolver->generateStateFilename($providerName);
        $appServiceProviderPath = $this->pathResolver->getServiceProviderFilePath();

        $this->artisan(self::MAKE_STATE_PROVIDER_COMMAND)
            ->expectsQuestion(self::STATE_PROVIDER_CLASS_NAME, $providerName)
            ->expectsOutputToContain('Success!')
            ->expectsOutputToContain("created: $filePath")
            ->expectsOutputToContain('Next: Open your new state provider class and start customizing it.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertFileExists($filePath);

        $appServiceProviderContent = $this->filesystem->get($appServiceProviderPath);
        $this->assertStringContainsString('use ApiPlatform\State\ProviderInterface;', $appServiceProviderContent);
        $this->assertStringContainsString("use App\State\\$providerName;", $appServiceProviderContent);
        $this->assertStringContainsString('$this->app->tag(MyStateProvider::class, ProviderInterface::class);', $appServiceProviderContent);

        $this->filesystem->delete($filePath);
    }

    public function testWhenStateProviderClassAlreadyExists(): void
    {
        $providerName = 'ExistingProvider';
        $existingFile = $this->pathResolver->generateStateFilename($providerName);
        $this->filesystem->put($existingFile, '<?php // Existing provider');

        $expectedError = \sprintf('[ERROR] The file "%s" can\'t be generated because it already exists.', $existingFile);

        $this->artisan(self::MAKE_STATE_PROVIDER_COMMAND)
            ->expectsQuestion(self::STATE_PROVIDER_CLASS_NAME, $providerName)
            ->expectsOutput($expectedError)
            ->assertExitCode(Command::FAILURE);

        $this->filesystem->delete($existingFile);
    }

    public function testMakeStateProviderCommandWithoutGivenClassName(): void
    {
        $providerName = 'NoEmptyClassName';
        $filePath = $this->pathResolver->generateStateFilename($providerName);

        $this->artisan(self::MAKE_STATE_PROVIDER_COMMAND)
            ->expectsQuestion(self::STATE_PROVIDER_CLASS_NAME, '')
            ->expectsOutput('[ERROR] This value cannot be blank.')
            ->expectsQuestion(self::STATE_PROVIDER_CLASS_NAME, $providerName)
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
