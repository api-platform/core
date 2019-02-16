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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * PersonToPet.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @ODM\Document
 */
class PersonToPet
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @ODM\ReferenceOne(targetDocument=Pet::class)
     * @Groups({"people.pets"})
     *
     * @var Pet
     */
    public $pet;

    /**
     * @ODM\ReferenceOne(targetDocument=Person::class)
     *
     * @var Person
     */
    public $person;

    public function getId()
    {
        return $this->id;
    }
}
