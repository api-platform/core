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
 *
 * @ApiResource()
 * @ORM\Entity
 */
class RelatedToUrlEncodedId
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="UrlEncodedId")
     * @ORM\JoinColumn(name="urlencodedid_id", referencedColumnName="id", nullable=false)
     * @Assert\NotNull
     */
    private $urlEncodedIdResource;

    /**
     * Gets dummyFriend.
     *
     * @return UrlEncodedId
     */
    public function getUrlEncodedIdResource()
    {
        return $this->urlEncodedIdResource;
    }

    /**
     * Sets UrlEncodedId.
     *
     * @param UrlEncodedId $urlEncodedIdResource the value to set
     */
    public function setUrlEncodedIdResource(UrlEncodedId $urlEncodedIdResource)
    {
        $this->urlEncodedIdResource = $urlEncodedIdResource;
    }
}
