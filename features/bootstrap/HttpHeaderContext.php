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
use Behatch\HttpCall\Request;

final class HttpHeaderContext implements Context
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
