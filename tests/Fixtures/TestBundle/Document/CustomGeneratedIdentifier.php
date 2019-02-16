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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Doctrine\Generator\Uuid;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Custom identifier.
 *
 * @ApiResource
 * @ODM\Document
 */
class CustomGeneratedIdentifier
{
    /**
     * @var Uuid
     *
     * @ODM\Id(strategy="CUSTOM", type="string", options={"class"="ApiPlatform\Core\Tests\Fixtures\TestBundle\Doctrine\Generator\DocumentUuidGenerator"})
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}
