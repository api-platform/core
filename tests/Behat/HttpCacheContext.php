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

use ApiPlatform\Tests\Fixtures\InvalidatorSpy;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class HttpCacheContext implements Context
{
    public function __construct(private readonly KernelInterface $kernel)
    {
    }

    /**
     * @Then :iris IRIs should be purged
     */
    public function irisShouldBePurged(string $iris): void
    {
        /** @var InvalidatorSpy $invalidator */
        $invalidator = $this->kernel->getContainer()->get('behat.driver.service_container')->get('api_platform.http_cache.invalidator_spy');

        $invalidatedTags = implode(',', $invalidator->getInvalidatedTags());
        $invalidator->clear();

        if ($iris !== $invalidatedTags) {
            throw new ExpectationFailedException(sprintf('IRIs "%s" does not match expected "%s".', $invalidatedTags, $iris));
        }
    }
}
