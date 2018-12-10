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

namespace ApiPlatform\Core;

final class Events
{
    const PRE_READ = 'api_platform.pre_read';
    const POST_READ = 'api_platform.post_read';

    const PRE_DESERIALIZE = 'api_platform.pre_deserialize';
    const POST_DESERIALIZE = 'api_platform.post_deserialize';

    const PRE_VALIDATE = 'api_platform.pre_validate';
    const POST_VALIDATE = 'api_platform.post_validate';

    const PRE_WRITE = 'api_platform.pre_write';
    const POST_WRITE = 'api_platform.post_write';

    const PRE_SERIALIZE = 'api_platform.pre_serialize';
    const POST_SERIALIZE = 'api_platform.post_serialize';

    const PRE_ADD_FORMAT = 'api_platform.pre_add_format';
    const POST_ADD_FORMAT = 'api_platform.post_add_format';
}
