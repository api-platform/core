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

namespace ApiPlatform\Tests\Behat;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PHPUnit\Framework\ExpectationFailedException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Context\Context;
use ApiPlatform\Tests\Fixtures\TestBundle\HttpCache\TagCollectorDefault;
use ApiPlatform\Tests\Fixtures\TestBundle\HttpCache\TagCollectorCustom;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class HttpCacheContext implements Context
{
    public function __construct(private readonly KernelInterface $kernel, private ContainerInterface $driverContainer)
    {
    }

    /**
     * @BeforeScenario @customTagCollector
     */
    public function registerCustomTagCollector(BeforeScenarioScope $scope): void
    {
        $this->disableReboot($scope);
        $this->driverContainer->set('api_platform.http_cache.tag_collector', new TagCollectorCustom());
    }

    /**
     * @Then :iris IRIs should be purged
     */
    public function irisShouldBePurged(string $iris): void
    {
        $purger = $this->kernel->getContainer()->get('behat.driver.service_container')->get('test.api_platform.http_cache.purger');

        $purgedIris = implode(',', $purger->getIris());
        $purger->clear();

        if ($iris !== $purgedIris) {
            throw new ExpectationFailedException(sprintf('IRIs "%s" does not match expected "%s".', $purgedIris, $iris));
        }
    }

    /**
     * this is necessary to allow overriding services
     * see https://github.com/FriendsOfBehat/SymfonyExtension/issues/149 for details
     */
    private function disableReboot(BeforeScenarioScope $scope){

        /** @var MinkContext $minkContext */
        $minkContext = $scope->getEnvironment()->getContext(MinkContext::class);
        $client = $minkContext->getSession()->getDriver()->getClient();
        $client->disableReboot();


    }
}
