<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Event;

/**
 * API Events.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Events
{
    /**
     * The PRE_CREATE event occurs after the object validation and before its persistence during a POST request.
     *
     * @var string
     */
    const PRE_CREATE = 'dunglas_json_ld_api.pre_create';
    /**
     * The PRE_UPDATE event occurs after the object validation and before its persistence during a PUT request.
     *
     * @var string
     */
    const PRE_UPDATE = 'dunglas_json_ld_api.pre_update';
    /**
     * The PRE_DELETE event occurs before the object deletion during a DELETE request.
     *
     * @var string
     */
    const PRE_DELETE = 'dunglas_json_ld_api.pre_delete';

    /**
     * The POST_CREATE event occurs after the object persistence during POST request.
     *
     * @var string
     */
    const POST_CREATE = 'dunglas_json_ld_api.post_create';
    /**
     * The POST_UPDATE event occurs after the object persistence during a PUT request.
     *
     * @var string
     */
    const POST_UPDATE = 'dunglas_json_ld_api.post_update';
    /**
     * The POST_DELETE event occurs after the object deletion during a DELETE request.
     *
     * @var string
     */
    const POST_DELETE = 'dunglas_json_ld_api.post_delete';
}
