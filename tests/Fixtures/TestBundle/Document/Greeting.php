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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource]
#[ApiResource(uriTemplate: '/people/{id}/sent_greetings{._format}', uriVariables: ['id' => new Link(fromClass: Person::class, identifiers: ['id'], toProperty: 'sender')], status: 200, operations: [new GetCollection()])]
#[ODM\Document]
class Greeting
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    #[ODM\Field]
    public $message = '';
    #[ODM\ReferenceOne(targetDocument: Person::class, inversedBy: 'sentGreetings', storeAs: 'id')]
    public $sender;
    #[ODM\ReferenceOne(targetDocument: Person::class)]
    public $recipient;

    public function getId(): ?int
    {
        return $this->id;
    }
}
