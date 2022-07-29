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
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\Document\InputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\Document\OutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\State\DummyDtoInputOutputProcessor;
use ApiPlatform\Tests\Fixtures\TestBundle\State\DummyDtoInputOutputProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Dummy InputOutput.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource(input: InputDto::class, output: OutputDto::class, processor: DummyDtoInputOutputProcessor::class, provider: DummyDtoInputOutputProvider::class)]
#[ODM\Document]
class DummyDtoInputOutput
{
    public function __construct()
    {
        $this->relatedDummies = new ArrayCollection();
    }
    /**
     * @var int The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int', nullable: true)]
    public $id;
    /**
     * @var string
     */
    #[ODM\Field]
    public $str;
    /**
     * @var int
     */
    #[ODM\Field(type: 'float')]
    public $num;
    #[ODM\ReferenceMany(targetDocument: RelatedDummy::class, storeAs: 'id', nullable: true)]
    public Collection|iterable|null $relatedDummies = null;
}
