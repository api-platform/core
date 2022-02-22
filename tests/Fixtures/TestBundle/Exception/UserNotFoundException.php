<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Exception;

final class UserNotFoundException extends \Exception
{
    public function __construct()
    {
        parent::__construct("User does not exists.");
    }
}
