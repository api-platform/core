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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8438;

use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8438\Issue8438TaskRuleConditionDto;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Uid\Uuid;

/**
 * Related entity with an application-assigned identifier (no DB-generated id).
 */
#[ORM\Entity]
#[Map(target: Issue8438TaskRuleConditionDto::class)]
class Issue8438TaskRuleCondition
{
    #[ORM\Id]
    #[ORM\Column(type: 'symfony_uuid', unique: true)]
    public Uuid $id;

    public function __construct(
        #[ORM\Column(type: 'string', length: 255)]
        public string $completeWhen = '',
        ?Uuid $id = null,
    ) {
        $this->id = $id ?? Uuid::v7();
    }
}
