<?php

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
