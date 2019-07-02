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
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

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
     * @param array $options An array of options to pass to the createKernel method
     */
    protected static function createClient(array $options = []): Client
    {
        $kernel = static::bootKernel($options);

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

        self::getHttpClient($client);
        self::getClient($client->getKernelBrowser());

        return $client;
    }
}
