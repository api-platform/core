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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * JSON Schema Context Dummy.
 *
 * @ApiResource
 *
 * @ORM\Entity
 */
class JsonSchemaContextDummy
{
    /**
     * @var int The id
     *
     * @ApiProperty(identifier=true)
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var array
     *
     * @ApiProperty(
     *     attributes={
     *         "json_schema_context"={
     *             "type"="array",
     *             "items"={"type"="string"},
     *             "minItems"=2,
     *             "maxItems"=2
     *         }
     *     },
     * )
     */
    private $things = ['pool', 'bag'];

    public function getId()
    {
        return $this->id;
    }

    public function getThings()
    {
        return $this->things;
    }
}
