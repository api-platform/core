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
use ApiPlatform\Metadata\Get;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Book.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
#[ApiResource(operations: [new Get(), new Get(uriTemplate: '/books/by_isbn/{isbn}{._format}', requirements: ['isbn' => '.+'], uriVariables: 'isbn')])]
#[ODM\Document]
class Book
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;
    #[ODM\Field(type: 'string', nullable: true)]
    public $name;
    #[ODM\Field(type: 'string')]
    public $isbn;

    public function getId()
    {
        return $this->id;
    }
}
