<?php

namespace ApiPlatform\Core\Bridge\Doctrine\Orm;

final class OrderDirection implements Enum
{
    const ASC = 'ASC';
    const DESC = 'DESC';

    public static function getValues(): array
    {
        return [self::ASC, self::DESC];
    }

    public static function getName(): string
    {
        return 'OrderDirection';
    }
}
