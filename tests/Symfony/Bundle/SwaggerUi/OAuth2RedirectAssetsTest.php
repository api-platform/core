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

namespace ApiPlatform\Tests\Symfony\Bundle\SwaggerUi;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OAuth2RedirectAssetsTest extends TestCase
{
    #[DataProvider('provideSwaggerUiAssetDirectories')]
    public function testOAuth2RedirectScriptIsShippedAlongsideHtml(string $directory): void
    {
        $html = $directory.'/oauth2-redirect.html';
        $script = $directory.'/oauth2-redirect.js';

        $this->assertFileExists($html, \sprintf('Expected %s to be present.', $html));
        $this->assertStringContainsString(
            'oauth2-redirect.js',
            (string) file_get_contents($html),
            \sprintf('%s should load oauth2-redirect.js.', $html)
        );
        $this->assertFileExists(
            $script,
            \sprintf('%s is referenced by oauth2-redirect.html but is missing on disk.', $script)
        );
    }

    public static function provideSwaggerUiAssetDirectories(): iterable
    {
        $root = \dirname(__DIR__, 4);

        yield 'symfony bundle' => [$root.'/src/Symfony/Bundle/Resources/public/swagger-ui'];
        yield 'laravel package' => [$root.'/src/Laravel/public/swagger-ui'];
    }
}
