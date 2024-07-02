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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Symfony\Action\NotFoundAction;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource(operations: [new Get(controller: NotFoundAction::class, read: false, output: false), new GetCollection()])]
#[ODM\Document]
class DisableItemOperation
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;
    /**
     * @var string The dummy name
     */
    #[ODM\Field]
    public $name;

    public function getId()
    {
        return $this->id;
    }
}
