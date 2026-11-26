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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\RuntimeResource;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class AttributeDefinition
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        #[ODM\Field]
        private string $name,
        #[ODM\Field]
        private string $type,
        #[ODM\Field(type: 'bool')]
        private bool $required = false,
        #[ODM\Field(type: 'bool')]
        private bool $collection = false,
        #[ODM\Field(type: 'hash')]
        private array $options = [],
        #[ODM\Field(type: 'bool')]
        private bool $filterable = false,
    ) {
    }
}
