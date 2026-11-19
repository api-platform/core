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

namespace ApiPlatform\Tests\Functional\JsonApi;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\PatchableDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class StrictMemberNamesAppKernel extends \AppKernel
{
    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/jsonapi_strict_member_names';
    }

    public function getLogDir(): string
    {
        return parent::getLogDir().'/jsonapi_strict_member_names';
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('api_platform', [
                'jsonapi' => ['strict_member_names' => true],
            ]);
        });
    }
}

final class ResourceObjectMemberNamesTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = true;

    protected static function getKernelClass(): string
    {
        return StrictMemberNamesAppKernel::class;
    }

    public static function getResources(): array
    {
        return [PatchableDummy::class];
    }

    public function testAttributeNamesStartWithAGloballyAllowedCharacter(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_patchable_dummies/1', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        foreach (array_keys($response->toArray()['data']['attributes']) as $name) {
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\x{0080}-\x{10FFFF}]/u', (string) $name);
        }
    }

    public function testIdAttributeIsExposedAsResourceId(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_patchable_dummies/1', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $response->toArray()['data']['attributes']['resourceId']);
    }

    public function testOpenApiSchemaMirrorsTheRenamedAttribute(): void
    {
        $response = self::createClient()->request('GET', '/docs.jsonopenapi', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('resourceId', $response->getContent());
        $this->assertStringNotContainsString('"_id"', $response->getContent());
    }
}
