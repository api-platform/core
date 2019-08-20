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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Test;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Base class for functional API tests.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class ApiTestCase extends KernelTestCase
{
    use ApiTestAssertionsTrait;

    protected function doTearDown(): void
    {
        parent::doTearDown();
        self::getClient(null);
    }

    /**
     * Creates a Client.
     *
     * @param array $kernelOptions  Options to pass to the createKernel method
     * @param array $defaultOptions Default options for the requests
     */
    protected static function createClient(array $kernelOptions = [], array $defaultOptions = []): Client
    {
        if (!class_exists(HttpClient::class)) {
            throw new RuntimeException('Run "composer require --dev symfony/http-client" to use the test client"');
        }

        $kernel = static::bootKernel($kernelOptions);

        try {
            /**
             * @var Client
             */
            $client = $kernel->getContainer()->get('test.api_platform.client');
        } catch (ServiceNotFoundException $e) {
            if (class_exists(KernelBrowser::class)) {
                throw new \LogicException('You cannot create the client used in functional tests if the "framework.test" config is not set to true.');
            }
            throw new \LogicException('You cannot create the client used in functional tests if the BrowserKit component is not available. Try running "composer require symfony/browser-kit".');
        }

        $client->setDefaultOptions($defaultOptions);

        self::getHttpClient($client);
        self::getClient($client->getKernelBrowser());

        return $client;
    }
}
