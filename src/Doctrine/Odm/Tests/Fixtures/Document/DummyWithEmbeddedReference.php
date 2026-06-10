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

namespace ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Document embedding a document that holds a reference, see
 * https://github.com/api-platform/core/issues/6376.
 */
#[ODM\Document]
class DummyWithEmbeddedReference
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\EmbedOne(targetDocument: EmbeddedReferenceHolder::class)]
    public ?EmbeddedReferenceHolder $embeddedReferenceHolder = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
