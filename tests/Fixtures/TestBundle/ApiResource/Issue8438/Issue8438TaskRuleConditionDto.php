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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8438;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8438\Issue8438TaskRuleCondition;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new Get(),
        new Post(),
    ],
    shortName: 'Issue8438TaskRuleCondition',
    stateOptions: new Options(entityClass: Issue8438TaskRuleCondition::class)
)]
#[Map(target: Issue8438TaskRuleCondition::class)]
class Issue8438TaskRuleConditionDto
{
    public Uuid $id;
    public string $completeWhen = '';

    public function __construct()
    {
        // Application-assigned identifier, generated before the entity is persisted
        $this->id = Uuid::v7();
    }
}
