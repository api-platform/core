<?php

namespace ApiPlatform\Core\Bridge\Doctrine\Orm;

interface Enum
{
    public static function getValues(): array;

    public static function getName(): string;
}
