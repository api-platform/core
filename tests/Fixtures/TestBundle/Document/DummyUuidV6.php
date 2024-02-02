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
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV6;


#[ApiResource(extraProperties: ['doctrine_mongodb' => ['execute_options' => ['allowDiskUse' => true]]], filters: ['my_dummy.mongodb.uuid_range'])]
#[ODM\Document]
class DummyUuidV6
{
    #[ODM\Id(strategy: 'NONE', type: 'string', nullable: true)]
    private ?Uuid $id = null;

    public function __construct()
    {
        $this->id = UuidV6::v6();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }
}
