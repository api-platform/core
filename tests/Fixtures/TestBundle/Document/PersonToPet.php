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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * PersonToPet.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
#[ODM\Document]
class PersonToPet
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;
    /**
     * @var Pet
     */
    #[Groups(['people.pets'])]
    #[ODM\ReferenceOne(targetDocument: Pet::class)]
    public $pet;
    /**
     * @var Person
     */
    #[ODM\ReferenceOne(targetDocument: Person::class)]
    public $person;

    public function getId()
    {
        return $this->id;
    }
}
