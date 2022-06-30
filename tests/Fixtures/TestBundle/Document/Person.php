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

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Person.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
#[ApiResource(normalizationContext: ['groups' => ['people.pets']])]
#[ODM\Document]
class Person
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;
    #[Groups(['people.pets'])]
    #[ODM\Field(type: 'string')]
    public $name;
    /**
     * @var ArrayCollection
     */
    #[Groups(['people.pets'])]
    #[ODM\ReferenceMany(targetDocument: PersonToPet::class, mappedBy: 'person')]
    public $pets;
    #[ODM\ReferenceMany(targetDocument: Greeting::class, mappedBy: 'sender')]
    public $sentGreetings;

    public function __construct()
    {
        $this->pets = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }
}
