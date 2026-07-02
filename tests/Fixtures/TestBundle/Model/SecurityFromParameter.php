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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Model;

use ApiPlatform\Metadata\Operation;

/**
 * Config-backed resource whose `security` expression is supplied through a container parameter
 * (`%app.security.admin_only%`). Used to verify the YAML/XML extractor resolves a whole-string
 * %param% into a working ExpressionLanguage expression (issue #8104).
 */
class SecurityFromParameter
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
