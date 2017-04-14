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

namespace ApiPlatform\Core\Util;

use Symfony\Component\HttpFoundation\Request;

/**
 * Utility functions for working with Symfony's HttpFoundation request.
 *
 * @internal
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class RequestParser
{
    private function __construct()
    {
    }

    /**
     * Gets a fixed request.
     *
     * @param Request $request
     *
     * @return Request
     */
    public static function parseAndDuplicateRequest(Request $request): Request
    {
        $query = self::parseRequestParams($request->getQueryString() ?? '');
        $body = self::parseRequestParams($request->getContent());

        return $request->duplicate($query, $body);
    }

    /**
     * Parses request parameters from the specified source.
     *
     * @author Rok Kralj
     *
     * @see http://stackoverflow.com/a/18209799/1529493
     *
     * @param string $source
     *
     * @return array
     */
    public static function parseRequestParams(string $source): array
    {
        // '[' is urlencoded in the input, but we must urldecode it in order
        // to find it when replacing names with the regexp below.
        $source = str_replace(urlencode('['), '[', $source);

        $source = preg_replace_callback(
            '/(^|(?<=&))[^=[&]+/',
            function ($key) {
                return bin2hex(urldecode($key[0]));
            },
            $source
        );

        // parse_str urldecodes both keys and values in resulting array.
        parse_str($source, $params);

        return array_combine(array_map('hex2bin', array_keys($params)), $params);
    }
}
