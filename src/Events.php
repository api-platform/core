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

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class Events
{
    public const QUERY_PARAMETER_VALIDATE = 'api_platform.query_parameter_validate';
    public const FORMAT_ADD = 'api_platform.format_add';

    public const PRE_READ = 'api_platform.pre_read';
    public const READ = 'api_platform.read';
    public const POST_READ = 'api_platform.post_read';

    public const PRE_DESERIALIZE = 'api_platform.pre_deserialize';
    public const DESERIALIZE = 'api_platform.deserialize';
    public const POST_DESERIALIZE = 'api_platform.post_deserialize';

    public const PRE_VALIDATE = 'api_platform.pre_validate';
    public const VALIDATE = 'api_platform.validate';
    public const POST_VALIDATE = 'api_platform.post_validate';

    public const PRE_WRITE = 'api_platform.pre_write';
    public const WRITE = 'api_platform.write';
    public const POST_WRITE = 'api_platform.post_write';

    public const PRE_SERIALIZE = 'api_platform.pre_serialize';
    public const SERIALIZE = 'api_platform.serialize';
    public const POST_SERIALIZE = 'api_platform.post_serialize';

    public const PRE_RESPOND = 'api_platform.pre_respond';
    public const RESPOND = 'api_platform.respond';
    public const POST_RESPOND = 'api_platform.post_respond';

    public const VALIDATE_EXCEPTION = 'api_platform.validate_exception';
}
