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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\OutputDtoDummy;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Dummy InputOutput.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(attributes={"output"=OutputDtoDummy::class})
 * @ODM\Document
 */
class DummyDtoOutputFallbackToSameClass
{
    /**
     * @var int The id
     *
<<<<<<< HEAD
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
=======
     * @ODM\Id(strategy="INCREMENT", type="integer", nullable=true)
>>>>>>> Add ODM docs for tests
     */
    private $id;

    /**
     * @var string
     *
<<<<<<< HEAD
     * @ORM\Column
=======
     * @ODM\Field
>>>>>>> Add ODM docs for tests
     */
    public $lorem;

    /**
     * @var string
     *
<<<<<<< HEAD
     * @ORM\Column
=======
     * @ODM\Field
>>>>>>> Add ODM docs for tests
     */
    public $ipsum;

    public function getId()
    {
        return $this->id;
    }
}
