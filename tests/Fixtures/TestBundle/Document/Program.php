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

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
#[ODM\Document]
class Program
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;
    #[ODM\Field(type: 'string')]
    public $name;
    #[ODM\Field(type: 'date')]
    public $date;
    #[ODM\ReferenceOne(targetDocument: User::class, storeAs: 'id')]
    public $author;

    public function getId()
    {
        return $this->id;
    }
}
