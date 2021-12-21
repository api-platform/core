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

namespace ApiPlatform\Core\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;

class SystemContext implements Context
{
    /** @Given class :clazz exists */
    public function classExists($clazz)
    {
        if (!class_exists($clazz)) {
            throw new PendingException(sprintf('Class %s is not defined, skipping test', $clazz));
        }
    }
}
