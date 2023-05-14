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

/**
 * This entity is configure in tests/Fixtures/app/config/api_platform/flex.yaml.
 */
class FlexConfig
{
    private $id;

    public function getId()
    {
        return $this->id;
    }
}
