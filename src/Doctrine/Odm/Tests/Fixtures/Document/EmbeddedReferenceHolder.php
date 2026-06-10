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
 * Embedded document holding a reference to another document.
 *
 * Reproduces the structure of https://github.com/api-platform/core/issues/6376
 * where an embedded document (which has no identifier of its own) carries a
 * reference to a top-level document.
 */
#[ODM\EmbeddedDocument]
class EmbeddedReferenceHolder
{
    #[ODM\Field(type: 'string')]
    public ?string $name = null;

    #[ODM\ReferenceOne(targetDocument: RelatedDummy::class, storeAs: 'id')]
    public ?RelatedDummy $relatedDummy = null;
}
