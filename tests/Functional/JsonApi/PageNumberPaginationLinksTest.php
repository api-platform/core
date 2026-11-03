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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\PageLinksDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PageNumberPaginationAppKernel extends \AppKernel
{
    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/jsonapi_page_number';
    }

    public function getLogDir(): string
    {
        return parent::getLogDir().'/jsonapi_page_number';
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('api_platform', [
                'collection' => [
                    'pagination' => [
                        'page_parameter_name' => 'number',
                        'items_per_page_parameter_name' => 'size',
                    ],
                ],
            ]);
        });
    }
}

final class PageNumberPaginationLinksTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = true;

    public static function getResources(): array
    {
        return [PageLinksDummy::class];
    }

    protected static function getKernelClass(): string
    {
        return PageNumberPaginationAppKernel::class;
    }

    public function testLinksDoNotDuplicateTheHoistedPageParameter(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_page_links_dummies?page[number]=2&page[size]=3', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame(2, $body['meta']['currentPage']);
        $links = $body['links'];
        $this->assertSame('/jsonapi_page_links_dummies?page%5Bnumber%5D=2&page%5Bsize%5D=3', $links['self']);
        $this->assertSame('/jsonapi_page_links_dummies?page%5Bnumber%5D=1&page%5Bsize%5D=3', $links['first']);
        $this->assertSame('/jsonapi_page_links_dummies?page%5Bnumber%5D=4&page%5Bsize%5D=3', $links['last']);
        $this->assertSame('/jsonapi_page_links_dummies?page%5Bnumber%5D=1&page%5Bsize%5D=3', $links['prev']);
        $this->assertSame('/jsonapi_page_links_dummies?page%5Bnumber%5D=3&page%5Bsize%5D=3', $links['next']);
    }

    public function testFollowingTheNextLinkMovesToTheNextPage(): void
    {
        $client = self::createClient();
        $headers = ['headers' => ['Accept' => 'application/vnd.api+json']];
        $next = $client->request('GET', '/jsonapi_page_links_dummies?page[number]=2&page[size]=3', $headers)->toArray()['links']['next'];

        $this->assertSame(3, $client->request('GET', $next, $headers)->toArray()['meta']['currentPage']);
    }
}
