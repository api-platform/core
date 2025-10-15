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

namespace ApiPlatform\Symfony\Bundle\Test;

use ApiPlatform\Metadata\IriConverterInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpClient\HttpClientTrait;

/**
 * Base class for functional API tests.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class ApiTestCase extends KernelTestCase
{
    use ApiTestAssertionsTrait;

    /**
     * If you're using RecreateDatabaseTrait, RefreshDatabaseTrait, ReloadDatabaseTrait from theofidry/AliceBundle, you
     * probably need to set this property to false in your test class to avoid recreating the database on each client creation.
     *
     * - `null` triggers a deprecation message and always boots the kernel
     * - `false` does not boot the kernel if it's already booted
     * - `true` always boots the kernel without any deprecation message
     */
    protected static ?bool $alwaysBootKernel = null;

    /**
     * Creates a Client.
     *
     * @param array $kernelOptions  Options to pass to the createKernel method
     * @param array $defaultOptions Default options for the requests
     */
    protected static function createClient(array $kernelOptions = [], array $defaultOptions = []): Client
    {
        if (null === static::$alwaysBootKernel) {
            trigger_deprecation(
                'api-platform/symfony',
                '4.1.0',
                'Currently, the kernel will always be booted when a new client is created, but in API Platform 5.0, it will not be booted unless you set `static::$alwaysBootKernel` to `true` (the default will be `false`). See https://github.com/api-platform/core/issues/6971 for more information.',
            );
        }

        if (static::$alwaysBootKernel || null === static::$alwaysBootKernel) {
            static::bootKernel($kernelOptions);
        }

        try {
            /**
             * @var Client
             */
            $client = self::getContainer()->get('test.api_platform.client');
        } catch (ServiceNotFoundException) {
            if (!class_exists(AbstractBrowser::class) || !trait_exists(HttpClientTrait::class)) {
                throw new \LogicException('You cannot create the client used in functional tests if the BrowserKit and HttpClient components are not available. Try running "composer require --dev symfony/browser-kit symfony/http-client".');
            }

            throw new \LogicException('You cannot create the client used in functional tests if the "framework.test" config is not set to true.');
        }

        $client->setDefaultOptions($defaultOptions);

        self::getHttpClient($client);
        self::getClient($client->getKernelBrowser());

        return $client;
    }

    /**
     * Finds the IRI of a resource item matching the resource class and the specified criteria.
     */
    protected function findIriBy(string $resourceClass, array $criteria): ?string
    {
        $container = static::getContainer();

        if (
            (
                !$container->has('doctrine')
                || null === $objectManager = $container->get('doctrine')->getManagerForClass($resourceClass)
            )
            && (
                !$container->has('doctrine_mongodb')
                || null === $objectManager = $container->get('doctrine_mongodb')->getManagerForClass($resourceClass)
            )
        ) {
            throw new \RuntimeException(\sprintf('"%s" only supports classes managed by Doctrine ORM or Doctrine MongoDB ODM. Override this method to implement your own retrieval logic if you don\'t use those libraries.', __METHOD__));
        }

        $item = $objectManager->getRepository($resourceClass)->findOneBy($criteria);
        if (null === $item) {
            return null;
        }

        return $this->getIriFromResource($item);
    }

    /**
     * Generate the IRI of a resource item.
     */
    protected function getIriFromResource(object $resource): ?string
    {
        /** @var IriConverterInterface $iriConverter */
        $iriConverter = static::getContainer()->get('api_platform.iri_converter');

        return $iriConverter->getIriFromResource($resource);
    }
}
