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

use Behat\Symfony2Extension\Context\KernelAwareContext;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class HttpCacheContext implements KernelAwareContext
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @Then :iris IRIs should be purged
     */
    public function irisShouldBePurged(string $iris)
    {
        $purger = $this->kernel->getContainer()->get('api_platform.http_cache.purger');

        $purgedIris = implode(',', $purger->getIris());
        $purger->clear();

        if ($iris !== $purgedIris) {
            throw new ExpectationFailedException(sprintf('IRIs "%s" does not match expected "%s".', $purgedIris, $iris));
        }
    }
}
