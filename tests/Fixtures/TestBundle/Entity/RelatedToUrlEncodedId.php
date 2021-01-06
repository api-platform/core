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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Related To URL Encoded ID represent an association for encoding special characters
 * TODO: We should not really need to have urlEncodedIdResource should we? Determine that the related resource has this requirement and then apply it as a requirement here would be preferable. Not sure about composite IDs though.
 *
 * @ApiResource(
 *     itemOperations={
 *         "get"={
 *             "method"="GET",
 *             "requirements"={"urlEncodedIdResource"=".*"}
 *         }
 *     },
 *     collectionOperations={
 *         "get"={
 *             "method"="GET",
 *             "requirements"={"urlEncodedIdResource"=".*"}
 *         },
 *         "post"
 *     }
 * )
 * @ORM\Entity
 */
class RelatedToUrlEncodedId
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="UrlEncodedId")
     * @ORM\JoinColumn(name="urlencodedid_id", referencedColumnName="id", nullable=false)
     * @Assert\NotNull
     */
    private $urlEncodedIdResource;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return UrlEncodedId
     */
    public function getUrlEncodedIdResource()
    {
        return $this->urlEncodedIdResource;
    }

    /**
     * @param UrlEncodedId $urlEncodedIdResource the value to set
     */
    public function setUrlEncodedIdResource(UrlEncodedId $urlEncodedIdResource)
    {
        $this->urlEncodedIdResource = $urlEncodedIdResource;
    }
}
