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
use Doctrine\ODM\MongoDB\Mapping\Annotations\ReferenceMany;
use Doctrine\ODM\MongoDB\Mapping\Annotations\ReferenceOne;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
#[Document]
class DoctrineDummy
{
    #[Id]
    public $id;

    #[ReferenceOne(targetDocument: DoctrineRelation::class)]
    public $foo;

    #[ReferenceMany(targetDocument: DoctrineRelation::class)]
    public $bar;

    #[ReferenceMany(targetDocument: DoctrineRelation::class, mappedBy: 'foo', storeAs: 'id')]
    protected $indexedFoo;

    #[Field(type: 'bin')]
    protected $bin;

    #[Field(type: 'bin_bytearray')]
    protected $binByteArray;

    #[Field(type: 'bin_custom')]
    protected $binCustom;

    #[Field(type: 'bin_func')]
    protected $binFunc;

    #[Field(type: 'bin_md5')]
    protected $binMd5;

    #[Field(type: 'bin_uuid')]
    protected $binUuid;

    #[Field(type: 'bin_uuid_rfc4122')]
    protected $binUuidRfc4122;

    #[Field(type: 'timestamp')]
    private $timestamp; // @phpstan-ignore-line

    #[Field(type: 'date')]
    private $date; // @phpstan-ignore-line

    #[Field(type: 'date_immutable')]
    private $dateImmutable; // @phpstan-ignore-line

    #[Field(type: 'float')]
    private $float; // @phpstan-ignore-line

    #[Field(type: 'bool')]
    private $bool; // @phpstan-ignore-line

    #[Field(type: 'custom_foo')]
    private $customFoo; // @phpstan-ignore-line

    #[Field(type: 'int')]
    private $int; // @phpstan-ignore-line

    #[Field(type: 'string')]
    private $string; // @phpstan-ignore-line

    #[Field(type: 'key')]
    private $key; // @phpstan-ignore-line

    #[Field(type: 'hash')]
    private $hash; // @phpstan-ignore-line

    #[Field(type: 'collection')]
    private $collection; // @phpstan-ignore-line

    #[Field(type: 'object_id')]
    private $objectId; // @phpstan-ignore-line

    #[Field(type: 'raw')]
    private $raw; // @phpstan-ignore-line

    public $notMapped;
}
