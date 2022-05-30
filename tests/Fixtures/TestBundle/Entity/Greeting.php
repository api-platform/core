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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ApiResource]
#[ApiResource(uriTemplate: '/people/{id}/sent_greetings.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\Person::class, identifiers: ['id'], toProperty: 'sender')], status: 200, operations: [new GetCollection()])]
class Greeting
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column
     */
    public $message = '';
    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="sentGreetings")
     * @ORM\JoinColumn(name="sender_id")
     */
    public $sender;
    /**
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="recipient_id", nullable=true)
     */
    public $recipient;

    public function getId(): ?int
    {
        return $this->id;
    }
}
