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
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\InputDto;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * DummyDtoNoOutput.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
#[ApiResource(input: InputDto::class, output: false)]
#[ODM\Document]
class DummyDtoNoOutput
{
    /**
     * @var int The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    /**
     * @var string
     */
    #[ODM\Field]
    public $lorem;
    /**
     * @var string
     */
    #[ODM\Field]
    public $ipsum;

    public function getId(): ?int
    {
        return $this->id;
    }
}
