<?php


namespace ApiPlatform\Core\Event;


final class ApiPlatformEvents
{
    const PRE_READ = 'api_platform.pre_read';
    const POST_READ = 'api_platform.post_read';

    const PRE_WRITE = 'api_platform.pre_write';
    const POST_WRITE = 'api_platform.post_write';

    const PRE_SERIALIZE = 'api_platform.pre_serialize';
    const POST_SERIALIZE = 'api_platform.post_serialize';

    const PRE_RESPOND = 'api_platform.pre_respond';
    const POST_RESPOND = 'api_platform.post_respond';

    const PRE_ADD_FORMAT = 'api_platform.pre_add_format';
    const POST_ADD_FORMAT = 'api_platform.post_add_format';
}
