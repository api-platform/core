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

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ApiResource
 * @ODM\Document
 */
class Greeting
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @ODM\Field
     */
    public $message = '';

    /**
     * @ODM\ReferenceOne(targetDocument=Person::class, inversedBy="sentGreetings", storeAs="id")
     */
    public $sender;

    /**
     * @ODM\ReferenceOne(targetDocument=Person::class)
     */
    public $recipient;

    public function getId(): int
    {
        return $this->id;
    }
}
