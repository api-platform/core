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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8143\ReferenceResponse;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OpenApiNameConverterAppKernel extends \AppKernel
{
    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/openapi_name_converter';
    }

    public function getLogDir(): string
    {
        return parent::getLogDir().'/openapi_name_converter';
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'serializer' => [
                    'name_converter' => 'serializer.name_converter.camel_case_to_snake_case',
                ],
            ]);
        });
    }
}

final class OpenApiNameConverterTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = true;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ReferenceResponse::class];
    }

    protected static function getKernelClass(): string
    {
        return OpenApiNameConverterAppKernel::class;
    }

    public function testGlobalNameConverterDoesNotLeakIntoOpenApiDocument(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray();
        $content = $response->getContent();

        // OpenAPI keys must stay camelCase even when a global name converter is configured.
        $this->assertStringContainsString('"operationId"', $content);
        $this->assertStringNotContainsString('"operation_id"', $content);
        $this->assertStringNotContainsString('"extension_properties"', $content);
        $this->assertStringNotContainsString('"external_docs"', $content);
        $this->assertStringNotContainsString('"request_bodies"', $content);
        $this->assertStringNotContainsString('"security_schemes"', $content);

        // The #[SerializedName('$ref')] metadata must still be honored.
        $responses = $json['paths']['/issue8143_reference_response']['post']['responses'];
        $this->assertArrayHasKey('$ref', $responses['401']);
        $this->assertSame('#/components/responses/401', $responses['401']['$ref']);
    }
}
