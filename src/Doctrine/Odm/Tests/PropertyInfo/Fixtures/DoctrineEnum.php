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

namespace ApiPlatform\Doctrine\Odm\Tests\PropertyInfo\Fixtures;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
#[Document]
class DoctrineEnum
{
    #[Id]
    public int $id;

    #[Field(enumType: EnumString::class)]
    protected EnumString $enumString;

    #[Field(type: 'int', enumType: EnumInt::class)]
    protected EnumInt $enumInt;

    #[Field(type: 'custom_foo', enumType: EnumInt::class)]
    protected EnumInt $enumCustom;
}
