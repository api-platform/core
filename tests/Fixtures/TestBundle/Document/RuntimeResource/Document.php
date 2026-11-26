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

#[ODM\Document(collection: 'runtime_document')]
#[ODM\Index(keys: ['resource' => 'asc'])]
class Document
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        #[ODM\Field]
        private string $resource,
        #[ODM\Field(type: 'hash')]
        private array $values = [],
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function setValues(array $values): void
    {
        $this->values = $values;
    }
}
