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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6384;

use ApiPlatform\Metadata\Get;
use Symfony\Component\HttpFoundation\Response;

#[Get(
    uriTemplate: 'accept_html',
    provider: [self::class, 'provide'],
    outputFormats: ['html' => ['text/html']],
    formats: ['html' => ['text/html']],
    extraProperties: ['_api_disable_swagger_provider' => true]
)]
class AcceptHtml
{
    public static function provide(): Response
    {
        return new Response('<h1>hello</h1>');
    }
}
