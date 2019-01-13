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

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behatch\HttpCall\Request;

final class HttpHeaderContext implements Context
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Sets the default Accept HTTP header to null (workaround to artificially remove it).
     *
     * @AfterStep
     */
    public function removeAcceptHeaderAfterRequest(AfterStepScope $event)
    {
        if (preg_match('/^I send a "[A-Z]+" request to ".+"/', $event->getStep()->getText())) {
            $this->request->setHttpHeader('Accept', null);
        }
    }

    /**
     * Sets the default Accept HTTP header to null (workaround to artificially remove it).
     *
     * @BeforeScenario
     */
    public function removeAcceptHeaderBeforeScenario()
    {
        $this->request->setHttpHeader('Accept', null);
    }
}
