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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm\PropertyInfo\Fixtures;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;
use Doctrine\ODM\MongoDB\Mapping\Annotations\ReferenceMany;
use Doctrine\ODM\MongoDB\Mapping\Annotations\ReferenceOne;

/**
 * @Document
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DoctrineDummy
{
    /**
     * @Id
     */
    public $id;

    /**
     * @ReferenceOne(targetDocument=DoctrineRelation::class)
     */
    public $foo;

    /**
     * @ReferenceMany(targetDocument=DoctrineRelation::class)
     */
    public $bar;

    /**
     * @ReferenceMany(targetDocument=DoctrineRelation::class, mappedBy="foo", storeAs="id")
     */
    protected $indexedFoo;

    /**
     * @Field(type="bin")
     */
    protected $bin;

    /**
     * @Field(type="bin_bytearray")
     */
    protected $binByteArray;

    /**
     * @Field(type="bin_custom")
     */
    protected $binCustom;

    /**
     * @Field(type="bin_func")
     */
    protected $binFunc;

    /**
     * @Field(type="bin_md5")
     */
    protected $binMd5;

    /**
     * @Field(type="bin_uuid")
     */
    protected $binUuid;

    /**
     * @Field(type="bin_uuid_rfc4122")
     */
    protected $binUuidRfc4122;

    /**
     * @Field(type="timestamp")
     */
    private $timestamp;

    /**
     * @Field(type="date")
     */
    private $date;

    /**
     * @Field(type="float")
     */
    private $float;

    /**
     * @Field(type="bool")
     */
    private $bool;

    /**
     * @Field(type="boolean")
     */
    private $boolean;

    /**
     * @Field(type="custom_foo")
     */
    private $customFoo;

    /**
     * @Field(type="int")
     */
    private $int;

    /**
     * @Field(type="integer")
     */
    private $integer;

    /**
     * @Field(type="string")
     */
    private $string;

    /**
     * @Field(type="key")
     */
    private $key;

    /**
     * @Field(type="file")
     */
    private $file;

    /**
     * @Field(type="hash")
     */
    private $hash;

    /**
     * @Field(type="collection")
     */
    private $collection;

    /**
     * @Field(type="object_id")
     */
    private $objectId;

    /**
     * @Field(type="raw")
     */
    private $raw;

    public $notMapped;
}
