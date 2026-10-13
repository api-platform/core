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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

/**
 * Attribute-defined resource whose `security` expression comes from a container parameter
 * (`%app.security.admin_only%` holds `is_granted("ROLE_ADMIN")`). Verifies the Symfony bridge
 * resolves whole-string %param% references declared through PHP attributes, mirroring the
 * YAML/XML extractor support (issue #8104).
 */
#[Get(
    uriTemplate: '/security_from_container_parameter/{id}',
    uriVariables: ['id'],
    security: '%app.security.admin_only%',
    provider: [SecurityFromContainerParameter::class, 'provide'],
)]
class SecurityFromContainerParameter
{
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self();
    }
}
