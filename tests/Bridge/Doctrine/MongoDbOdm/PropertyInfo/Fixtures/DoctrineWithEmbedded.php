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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm\PropertyInfo\Fixtures;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbedMany;
use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbedOne;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;

/**
 * @Document
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DoctrineWithEmbedded
{
    /**
     * @Id
     */
    public $id;

    /**
     * @EmbedOne(targetDocument=DoctrineEmbeddable::class)
     */
    protected $embedOne;

    /**
     * @EmbedMany(targetDocument=DoctrineEmbeddable::class)
     */
    protected $embedMany;
}
