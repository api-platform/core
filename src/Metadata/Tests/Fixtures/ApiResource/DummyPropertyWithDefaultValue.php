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

namespace ApiPlatform\Metadata\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * DummyPropertyWithDefaultValue.
 */
#[ApiResource(normalizationContext: ['groups' => ['dummy_read']], denormalizationContext: ['groups' => ['dummy_write']])]
class DummyPropertyWithDefaultValue
{
    #[Groups('dummy_read')]
    private ?int $id = null;
    /**
     * @var string|null
     */
    #[Groups(['dummy_read', 'dummy_write'])]
    public $foo = 'foo';

    public function getId(): ?int
    {
        return $this->id;
    }
}
