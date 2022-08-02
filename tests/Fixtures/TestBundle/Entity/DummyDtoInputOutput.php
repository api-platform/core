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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\InputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\State\DummyDtoInputOutputProcessor;
use ApiPlatform\Tests\Fixtures\TestBundle\State\DummyDtoInputOutputProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dummy InputOutput.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource(input: InputDto::class, output: OutputDto::class, processor: DummyDtoInputOutputProcessor::class, provider: DummyDtoInputOutputProvider::class)]
#[ORM\Entity]
class DummyDtoInputOutput
{
    public function __construct()
    {
        $this->relatedDummies = new ArrayCollection();
    }
    /**
     * @var int The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public $id;
    /**
     * @var string
     */
    #[ORM\Column(type: 'string')]
    public $str;
    /**
     * @var float
     */
    #[ORM\Column(type: 'float')]
    public $num;
    #[ORM\ManyToMany(targetEntity: RelatedDummy::class)]
    public Collection|iterable|null $relatedDummies = null;
}
