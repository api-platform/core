<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle;

/**
 * API Events.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DunglasJsonLdApiEvents
{
    /**
     * The PRE_CREATE event occurs after the object validation and before its persistence during POST request.
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
     * The PRE_DELETE event occurs before the object deletion.
     */
    const PRE_DELETE = 'dunglas_json_ld_api.pre_delete';
}
