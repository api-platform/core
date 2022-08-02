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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Jsonld Context Dummy.
 */
#[ApiResource]
#[ODM\Document]
class JsonldContextDummy
{
    /**
     * @var int The id
     */
    #[ApiProperty(identifier: true)]
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    /**
     * @var string The dummy person
     */
    #[ApiProperty(
        jsonldContext: ['@id' => 'https://example.com/id', '@type' => '@id', 'foo' => 'bar']
    )]
    private $person;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setPerson($person): void
    {
        $this->person = $person;
    }

    public function getPerson()
    {
        return $this->person;
    }
}
