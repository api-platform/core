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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\InputDto;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * DummyDtoNoOutput.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * @ODM\Document
 *
 * @ApiResource(
 *     attributes={
 *         "input"=InputDto::class,
 *         "output"=false
 *     }
 * )
 */
class DummyDtoNoOutput
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ODM\Field
     */
    public $lorem;

    /**
     * @var string
     *
     * @ODM\Field
     */
    public $ipsum;

    public function getId()
    {
        return $this->id;
    }
}
