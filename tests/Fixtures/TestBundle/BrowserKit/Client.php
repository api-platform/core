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

namespace ApiPlatform\Tests\Fixtures\TestBundle\BrowserKit;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Request as DomRequest;
use Symfony\Component\HttpFoundation\Request;

class Client extends KernelBrowser
{
    /**
     * {@inheritdoc}
     */
    protected function filterRequest(DomRequest $request): Request
    {
        $request = parent::filterRequest($request);

        foreach ($request->headers->all() as $key => $value) {
            if ([null] === $value) {
                $request->headers->remove($key);
            }
        }

        return $request;
    }
}
