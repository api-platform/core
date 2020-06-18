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
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @author Daniel West <daniel@silverback.is>
 *
 * Resource with an ID that will be URL encoded
 *
 * @ODM\Document
 *
 * @ApiResource(
 *     itemOperations={
 *         "get"={
 *             "method"="GET",
 *             "requirements"={"id"=".+"}
 *         }
 *     }
 * )
 */
class UrlEncodedId
{
    /**
     * @ODM\Id(strategy="none")
     */
    private $id = '%encode:id';

    public function getId()
    {
        return $this->id;
    }
}
