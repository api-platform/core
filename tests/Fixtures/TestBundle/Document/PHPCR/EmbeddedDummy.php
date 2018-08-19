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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\PHPCR;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * Embedded Dummy.
 *
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 *
 * @ApiResource(
 *     attributes={"filters"={"my_dummy.phpcr.boolean"}},
 *     itemOperations={"get", "put", "delete", "groups"={"method"="GET", "path"="/embedded_dummies_groups/{id}", "normalization_context"={"groups"={"embed"}}}}
 * )
 * @PHPCRODM\Document(referenceable=true)
 */
class EmbeddedDummy
{
    /**
     * @var int The id
     *
     * @PHPCRODM\Id
     */
    private $id;

    /**
     * @PHPCRODM\Node
     */
    public $node;

    /**
     * @PHPCRODM\ParentDocument()
     */
    public $parentDocument;

    /**
     * @var string The dummy name
     *
     * @PHPCRODM\Field(type="string")
     */
    private $name;

    /**
     * @var \DateTime A dummy date
     *
     * @PHPCRODM\Field(type="date")
     * @Assert\DateTime
     */
    public $dummyDate;

    /**
     * @PHPCRODM\Child
     */
    public $embeddedDummy;

    /**
     * @var RelatedDummy A related dummy
     *
     * @PHPCRODM\ReferenceOne(targetDocument="RelatedDummy")
     */
    public $relatedDummy;

    public static function staticMethod()
    {
    }

    public function __construct()
    {
        $this->embeddedDummy = new EmbeddableDummy();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getEmbeddedDummy(): EmbeddableDummy
    {
        return $this->embeddedDummy;
    }

    public function setEmbeddedDummy(EmbeddableDummy $embeddedDummy)
    {
        $this->embeddedDummy = $embeddedDummy;
    }

    /**
     * @return \DateTime
     */
    public function getDummyDate()
    {
        return $this->dummyDate;
    }

    public function setDummyDate(\DateTime $dummyDate)
    {
        $this->dummyDate = $dummyDate;
    }

    /**
     * @return RelatedDummy
     */
    public function getRelatedDummy()
    {
        return $this->relatedDummy;
    }

    public function setRelatedDummy(RelatedDummy $relatedDummy)
    {
        $this->relatedDummy = $relatedDummy;
    }
}
